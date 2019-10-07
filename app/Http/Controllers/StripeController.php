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
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));
        $account = \Stripe\OAuth::token([
            'code' => $request->token,
            'grant_type' => 'authorization_code'
        ]);

        // Save user Stripe account ID
        auth()->user()->fill([
            'stripe_connect_id' => $account->stripe_user_id
        ]);

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
        if (!$user->stripe_connect_id) {
            return response()->json([
                'error' => 'User hasn\'t registered to receive payments yet'
            ], 405);
        }

        // Retrieve Stripe account balance
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));
        return \Stripe\Balance::retrieve(['stripe_account' => auth()->user()->stripe_connect_id]);
    }

    /**
     * Withdraw Stripe balance for user
     *
     * @param Request $request
     * @return \Stripe\Payout
     */
    public function withdraw(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));
        return \Stripe\Payout::create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'metadata' => [
                'user_id' => auth()->user()->id
            ]
        ], ['stripe_account' => auth()->user()->stripe_connect_id]);
    }

    /**
     * Prepare a SetupIntent to save card for user
     *
     * @param Request $request
     * @return \Stripe\SetupIntent
     */
    public function prepareSource(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));
        return \Stripe\SetupIntent::create([
            'usage' => 'on_session',
            'metadata' => [
                'user_id' => auth()->user()->id
            ]
        ]);
    }

    /**
     * Retrieve users saved payment methods
     *
     * @param Request $request
     * @return \Stripe\PaymentMethod
     */
    public function savedSources(Request $request) {
        // Check Stripe customer exists
        if (!$user->stripe_connect_id) {
            return response()->json([
                'error' => 'User hasn\'t saved any payment methods yet'
            ], 405);
        }

        // Retrieve customer payment methods
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));
        $methods = \Stripe\PaymentMethod::all([
            'customer' => auth()->user()->stripe_customer_id,
            'type' => 'card',
        ]);
        $sources = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id)
            ->sources->data;

        return response()->json(array_merge($methods, $sources));
    }
}
