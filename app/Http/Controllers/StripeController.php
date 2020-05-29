<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'grant_type' => 'authorization_code',
            'code' => $request->token
        ]);
        \Stripe\Account::update(
            $acount->stripe_user_id,
            [
                'settings' => [
                    'payouts' => [
                        'schedule' => [ 'interval' => 'manual' ]
                    ]
                ]
            ]
        );

        Log::notice($account);

        // Save user Stripe account ID
        auth()->user()->fill([
            'stripe_connect_id' => $account->stripe_user_id,
            'default_currency' => $account->default_currency,
            'country' => $account->country,
            'name' => $account->business_profile->name
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
            return response()->json([]);
        }

        // Retrieve Stripe account balance
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $balance = \Stripe\Balance::retrieve(
            ['stripe_account' => auth()->user()->stripe_connect_id]
        );

        return $balance;
    }

    /**
     * Retrieve user Stripe dashboard link
     *
     * @param Request $request
     * @return \Stripe\Balance
     */
    public function loginLink(Request $request) {
        // Retrieve Stripe account balance
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\Account::createLoginLink(auth()->user()->stripe_connect_id);
    }

    /**
     * Retrieve user Stripe account
     *
     * @param Request $request
     * @return \Stripe\Account
     */
    public function account(Request $request) {
        // Retrieve Stripe account balance
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\Account::retrieve(auth()->user()->stripe_connect_id);
    }

    /**
     * Retrieve basic Stripe account info
     *
     * @param Request $request
     * @param int $id
     * @return \Stripe\Account
     */
    public function basicAccount(Request $request, $id) {
        $user = User::find($id);
        $stripe = null;

        // Retrieve Stripe account balance
        if (!!$user->stripe_connect_id) {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $stripe = \Stripe\Account::retrieve($user->stripe_connect_id);
        }

        return response()->json([
            'country' => $stripe->country ?? null,
            'default_currency' => $stripe->default_currency ?? 'aud',
            'business_type' => $stripe->business_type ?? null,
            'can_accept_payments' => !!$stripe->id ?? false
        ]);
    }

    /**
     * Withdraw Stripe balance for user
     *
     * @param Request $request
     * @return \Stripe\Payout
     */
    public function withdraw(Request $request) {
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
     * Get Stripe withdrawls for user
     *
     * @param Request $request
     * @return \Stripe\Payout
     */
    public function listWithdraw(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        return \Stripe\Payout::all([
            'limit' => 3
        ], ['stripe_account' => auth()->user()->stripe_connect_id])->data;
    }

    /**
     * Save a Payout destination for user
     *
     * @param Request $request
     * @return \Stripe\Account
     */
    public function saveDestination(Request $request) {
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
     * Save method
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveSource(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        // If no customer create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create(["email" => auth()->user()->email]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
        } else {
            $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);
        }

        // Save PaymentMethod
        $id = auth()->user()->stripe_connect_id;
        Log::alert("Saving Payment Method For Account {$id}");
        $payment_method = \Stripe\PaymentMethod::retrieve($request->id);
        $payment_method->attach([
            'customer' => $customer->id
        ]);
        //, ['stripe_account' => auth()->user()->stripe_connect_id]);
        return $payment_method;
    }

    /**
     * Delete method
     *
     * @param Request $request
     * @param string $method
     * @return \Illuminate\Http\Response
     */
    public function deleteSource(Request $request, string $method) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        // Get customer
        $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);

        // Delete PaymentMethod
        $payment_method = \Stripe\PaymentMethod::retrieve($method);
        $payment_method->detach();
        return response()->json(null, 204);
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

    /**
     * Retrieve users saved payment method
     *
     * @param Request $request
     * @param string $method
     * @return \Stripe\PaymentMethod
     */
    public function source(Request $request, string $method) {
        // Check Stripe customer exists
        if (!auth()->user()->stripe_customer_id) {
            return response()->json([
                'error' => 'User hasn\'t saved any payment methods yet'
            ], 405);
        }

        // Retrieve customer payment methods
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        return \Stripe\PaymentMethod::retrieve($method);
    }
}
