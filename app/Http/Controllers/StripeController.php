<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class StripeController extends Controller
{
    /**
     * User user code to register with Stripe
     *
     * @param Request $request
     * @return App\User
     */
    public function register(Request $request) {
        // Register user with Stripe using OAuth token
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $account = \Stripe\OAuth::token([
            'code' => $request->token,
            'grant_type' => 'authorization_code'
        ]);

        // Save user Stripe account ID
        auth()->user()->fill([
            'stripe_connect_id' => $account->stripe_user_id
        ])->save();

        return auth()->user();
    }


    /**
     * Retrieve user Stripe balance
     *
     * @param Request $request
     * @return \Stripe\Balance
     */
    public function balance(Request $request) {
        // Check Stripe account exists
        if (!auth()->user()->stripe_connect_id) {
            return response()->json([
                'error' => 'User hasn\'t registered to receive payments yet'
            ], 405);
        }

        // Retrieve Stripe account balance
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\Balance::retrieve(['stripe_account' => auth()->user()->stripe_connect_id]);
    }

    /**
     * Withdraw Stripe balance for user
     *
     * @param Request $request
     * @return \Stripe\Payout
     */
    public function withdraw(Request $request) {
        // TODO: Send withdrawl notification

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        return \Stripe\Payout::create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'metadata' => [
                'user_id' => auth()->user()->id
            ]
        ], ['stripe_account' => auth()->user()->stripe_connect_id]);
    }

    /**
     * Save a Payout destination for user
     *
     * @param Request $request
     * @return \Stripe\Account
     */
    public function saveDestination(Request $request) {
        // TODO: Send new withdrawl destination notification
        
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        return \Stripe\Account::createExternalAccount(
            auth()->user()->stripe_connect_id,
            ['external_account' => $request->token]
        );
    }

    /**
     * Retrieve users saved payout destinations
     *
     * @param Request $request
     * @return \Stripe\PaymentMethod
     */
    public function destinations(Request $request) {
        // Check Stripe customer exists
        if (!auth()->user()->stripe_connect_id) {
            return response()->json([
                'error' => 'User hasn\'t saved any payment methods yet'
            ], 405);
        }

        // Retrieve customer payment methods
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\Account::allExternalAccounts(auth()->user()->stripe_connect_id);
    }

    /**
     * Prepare a SetupIntent to save card for user
     *
     * @param Request $request
     * @return \Stripe\SetupIntent
     */
    public function prepareSource(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\SetupIntent::create([
            'usage' => 'on_session',
            'metadata' => [
                'user_id' => auth()->user()->id
            ]
        ]);
    }

    /**
     * Save method after PaymentIntent success
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveSource(Request $request) {
        \Stripe\Stripe::setApiKey('sk_test_ccu7Gl8YxOlksae8zncTMTiE');

        // Save intent card
        if ($request->stripe_transaction_id) {
            $intent = \Stripe\PaymentIntent::retrieve($request->stripe_transaction_id);
            $payment_method = \Stripe\PaymentMethod::retrieve($intent->payment_method);
            $payment_method->attach(['customer' => auth()->user()->stripe_customer_id]);
            return $payment_method;
        }

        // If no customer create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create(["email" => auth()->user()->email]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
        } else {
            $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);
        }

        // Save source card
        $source = \Stripe\Customer::createSource(
            auth()->user()->stripe_customer_id,
            ['source' => $request->source]
        );
        return $source;
    }

    /**
     * Retrieve users saved payment methods
     *
     * @param Request $request
     * @return \Stripe\PaymentMethod
     */
    public function sources(Request $request) {
        // Check Stripe customer exists
        if (!auth()->user()->stripe_customer_id) {
            return response()->json([
                'error' => 'User hasn\'t saved any payment methods yet'
            ], 405);
        }

        // Retrieve customer payment methods
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $methods = \Stripe\PaymentMethod::all([
            'customer' => auth()->user()->stripe_customer_id,
            'type' => 'card',
        ])['data'];
        $sources = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id)
            ->sources->data;

        return array_merge($methods, $sources);
    }
}
