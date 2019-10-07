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
     * @return \Illuminate\Http\Response
     */
    public function prepare(Request $request) {
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));

        // Create transaction and PaymentIntent
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'metadata' => [
                'user_id' => auth()->user()->id,
                'for_user_id' => $request->for_id
            ]
        ]);

        // DEBUG: Not finding for_user_id
        $transaction = Transaction::create([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'stripe_transaction_id' => $intent->id,
            'status' => 'pending',
            'type' => 'intent',
            'for_user_id' => $request->for_id,
            'from_user_id' => auth()->user()->id
        ]);

        return response()->json([
            'intent' => $intent,
            'transaction' => $transaction
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_PUBLIC_KEY'));

        // Get transaction references
        $transaction = Transaction::find($id);

        switch ($request->type) {
            case 'intent':
                // Check if type was just switched to PaymentIntent
                if (!$transaction->stripe_transaction_id) {
                    $intent = \Stripe\PaymentIntent::create([
                        'amount' => $request->amount,
                        'currency' => $request->currency,
                        'metadata' => [
                            'user_id' => auth()->user()->id,
                            'for_user_id' => $request->for_id
                        ]
                    ]);
                } else {
                    \Stripe\PaymentIntent::update(
                        $request->stripe_transaction_id,
                        $request->all()
                    );
                }
                // Update any sent data
                $transaction->fill($request->all());

                // Return updated data
                return response()->json([
                    'intent' => $intent,
                    'transaction' => Transaction::find($id)
                ]);
                break;

            case 'source':
                // If previously using a PaymentIntent, cancel the intent
                if ($request->stripe_transaction_id) {
                    $intent = \Stripe\PaymentIntent::retrieve($request->stripe_transaction_id);
                    $intent->cancel();
                }

                // If transaction has a stripe payment id then change to source
                $transaction->fill($request->all());

                // Return updated data
                return response()->json(Transaction::find($id));
        }
    }

    /**
     * Process transaction payment
     *
     * // TODO: If auth()->user()->settings['requires_verification] then require email verification before processing payment
     * // TODO: Implement webhooks to process PaymentIntent completion
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request) {
        if ($request->type != 'source' && $request->type != 'balance') {
            return response()->json([
                'error' => 'Transaction isn\'t using a source payment object'
            ], 400);
        }

        \Stripe\Stripe::setApiKey('sk_test_ccu7Gl8YxOlksae8zncTMTiE');

        // If no customer create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create([
                "email" => auth()->user()->email
            ]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id]);
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
                    "currency" => $request->currency,
                    "source" => $request->source,
                    "transfer_data" => [
                        "destination" => User::find($request->for_id)->stripe_connect_id,
                    ],
                ]);

                if (!$request->transaction_id) {
                    $transaction = Transaction::create($request->all());
                    $transaction->fill([
                        'stripe_transaction_id' => $charge->id,
                        'status' => $charge->status,
                    ]);
                } else {
                    $transaction = Transaction::find($request->transaction_id);
                    $transaction->fill($request->all());
                    $transaction->fill([
                        'stripe_transaction_id' => $charge->id,
                        'status' => $charge->status
                    ]);
                }

                return $charge;

            case 'balance':
                // TODO: create transaction for fee amount to Wallet connect account

                // Create transaction on behalf of users connected account
                $charge = \Stripe\Transfer::create([
                    "amount" => $request->amount,
                    "currency" => $request->currency,
                    "destination" => User::find($request->for_id)->stripe_connect_id
                ], ['stripe_account' => auth()->user()->stripe_connect_id]);

                if (!$request->transaction_id) {
                    $transaction = Transaction::create($request->all());
                    $transaction->fill([
                        'stripe_transaction_id' => $charge->id,
                        'status' => 'succeeded'
                    ]);
                } else {
                    $transaction = Transaction::find($request->transaction_id);
                    $transaction->fill($request->all());
                    $transaction->fill([
                        'stripe_transaction_id' => $charge->id,
                        'status' => 'succeeded'
                    ]);
                }

                return response()->json([
                    'charge' => $charge,
                    'transaction' => $transaction
                ]);
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
