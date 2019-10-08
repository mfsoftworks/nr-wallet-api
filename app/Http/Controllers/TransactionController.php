<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Prepare a PaymentIntent for Stripe
     *
     * @param Request $request
     * @return \Stripe\PaymentIntent
     */
    public function prepare(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        // If no customer, create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create([
                "email" => auth()->user()->email
            ]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
        } else {
            $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);
        }

        // Create PaymentIntent, default amount to 100, fee to 50
        return \Stripe\PaymentIntent::create([
            'amount' => 100,
            'application_fee_amount' => 50,
            'currency' => $request->currency,
            'customer' => $customer->id,
            'setup_future_usage' => 'on_session',
            'metadata' => [
                'user_id' => auth()->user()->id,
                'for_user_id' => $request->for_user_id
            ],
            "transfer_data" => [
                "destination" => User::find($request->for_user_id)->stripe_connect_id,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return response()->json(Transaction::find($id));
    }

    /**
     * Process transaction payment
     *
     * // TODO: If auth()->user()->settings['payment_auth'] then require email verification before processing payment
     * // TODO: Implement webhooks to process PaymentIntent completion
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request) {
        \Stripe\Stripe::setApiKey('sk_test_ccu7Gl8YxOlksae8zncTMTiE');

        // If no customer create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create(["email" => auth()->user()->email]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
        } else {
            $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);
        }

        // Create Transaction
        $transaction = Transaction::create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'stripe_transaction_id' => $request->stripe_transaction_id,
            'sender_fee' => $request->wallet_fee,
            'international_fee' => $request->international_fee,
            'status' => 'pending',
            'type' => $request->type,
            'for_user_id' => $request->for_user_id,
            'from_user_id' => auth()->user()->id
        ]);

        // Do final updates for PaymentIntent
        if ($request->type == 'intent') {
            // Update PaymentIntent with final info before processing
            return \Stripe\PaymentIntent::update(
                $request->stripe_transaction_id,
                [
                    'amount' => $request->amount,
                    'application_fee_amount' => $request->wallet_fee,
                    'currency' => $request->currency,
                    'description' => $request->description
                ]
            );
        }

        // If PaymentIntent not needed, cancel the intent
        if ($request->stripe_transaction_id) {
            $intent = \Stripe\PaymentIntent::retrieve($request->stripe_transaction_id);
            $intent->cancel();
        }

        // If save card, save source to customer
        if ($request->source && $request->save_card) {
            \Stripe\Customer::createSource(
                auth()->user()->stripe_customer_id,
                ['source' => $request->source]
            );
        }

        // Process payment of source transaction
        switch ($request->type) {
            case 'source':
                // Create charge with source token
                $charge = \Stripe\Charge::create([
                    "amount" => $request->amount,
                    'application_fee_amount' => $request->wallet_fee,
                    "currency" => $request->currency,
                    "source" => $request->source,
                    "customer" => $request->save_card ? auth()->user()->stripe_customer_id : null,
                    'description' => $request->description,
                    "transfer_data" => [
                        "destination" => User::find($request->for_user_id)->stripe_connect_id,
                    ],
                ]);

                // Update transaction
                $transaction->fill([
                    'stripe_transaction_id' => $charge->id,
                    'status' => $charge->status
                ]);

                return $charge;

            case 'balance':
                // Create transaction for fee amount to Wallet connect account
                if (env('WALLET_CONNECT_ACCOUNT') != auth()->user()->stripe_connect_id) {
                    \Stripe\Transfer::create([
                        "amount" => $request->wallet_fee,
                        "currency" => $request->currency,
                        "destination" => env('WALLET_CONNECT_ACCOUNT'),
                        'description' => "Wallet Fee for: {$request->description}"
                    ], ['stripe_account' => auth()->user()->stripe_connect_id]);
                }

                // Create transaction on behalf of users connected account
                $charge = \Stripe\Transfer::create([
                    "amount" => ($request->amount - $request->wallet_fee),
                    "currency" => $request->currency,
                    "destination" => User::find($request->for_user_id)->stripe_connect_id,
                    'description' => $request->description
                ], ['stripe_account' => auth()->user()->stripe_connect_id]);

                // Update transaction
                $transaction->fill([
                    'stripe_transaction_id' => $charge->id,
                    'status' => $charge->status
                ]);

                return $charge;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        return Transaction::delete($id);
    }
}
