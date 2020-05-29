<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\User;
use Illuminate\Http\Request;
use Log;
use Carbon;

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
        $amount = $request->amount ?? 100;
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'application_fee_amount' => floor($amount * env('WALLET_STRIPE_FEES', 0.05)),
            'currency' => auth()->user()->default_currency ?? env('DEFAULT_CURRENCY', 'aud'),
            'customer' => $customer->id,
            'metadata' => [
                'user_id' => auth()->user()->id,
                'for_user_id' => $request->for_user_id
            ],
            'payment_method_types' => [
                'card'
            ],
            'setup_future_usage' => 'on_session',
            "transfer_data" => [
                "destination" => User::find($request->for_user_id)->stripe_connect_id,
            ],
        ], [
            'idempotency_key' => $request->nonce
        ]);

        // Create Transaction
        $transaction = Transaction::create([
            'amount' => $amount,
            'currency' => auth()->user()->default_currency ?? env('DEFAULT_CURRENCY', 'aud'),
            'description' => $request->description,
            'stripe_transaction_id' => $intent->id,
            'status' => 'hidden',
            'type' => 'card',
            'for_user_id' => $request->for_user_id,
            'from_user_id' => auth()->user()->id,
            'nonce' => $request->nonce
        ]);

        return $intent;
    }

    /**
     * Get transactions for authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        // TODO: Add `date between` check

        $sent = auth()->user()
            ->sent()
            ->whereDate('created_at', '>=', Carbon::now()->subMonths(3))
            ->whereNotIn('for_user_id', [auth()->user()->id])
            ->where('status', '!=', 'hidden')
            ->latest();
        $received = auth()->user()
            ->received()
            ->whereDate('created_at', '>=', Carbon::now()->subMonths(3))
            ->where('status', '!=', 'hidden')
            ->latest();

        // Return notifications for authenticated user, within the last 4 weeks
        return response()->json([
            "sent" => $sent->get(),
            "received" => $received->get()
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
     * Updte PaymentIntent data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
        \Stripe\Stripe::setApiKey('sk_test_ccu7Gl8YxOlksae8zncTMTiE');

        // If no customer create customer
        if (!auth()->user()->stripe_customer_id) {
            $customer = \Stripe\Customer::create(["email" => auth()->user()->email]);
            auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
        } else {
            $customer = \Stripe\Customer::retrieve(auth()->user()->stripe_customer_id);
        }

        // Update PaymentIntent with final info before processing
        $intent = \Stripe\PaymentIntent::update(
            $request->stripe_transaction_id, [
                'amount' => $request->amount,
                'application_fee_amount' => floor($request->amount * env('WALLET_STRIPE_FEES', 0.05)),
                'currency' => $request->currency,
                'description' => $request->description
            ]
        );

        // Update Transaction
        $transaction = Transaction::where('stripe_transaction_id', $request->stripe_transaction_id)->first();
        $transaction->fill([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description
        ])->save();

        return $intent;
    }

    /**
     * Process transaction payment
     *
     * TODO: If auth()->user()->settings['payment_auth'] then require verification before processing payment
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

        // Update Transaction
        $transaction = Transaction::where('stripe_transaction_id', $request->stripe_transaction_id)->first();
        $transaction->fill([
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'status' => $request->status,
            'type' => $request->type
        ])->save();

        Log::notice($transaction);

        // Process payment of source transaction
        switch ($request->type) {
            case 'balance':
                $fee_amount = floor($request->amount * env('WALLET_STRIPE_FEES', 0.05));

                // Create transaction for fee amount to Wallet connect account
                $fee = \Stripe\Charge::create([
                    "amount" => $fee_amount >= 50 ? $fee_amount : 50,
                    "currency" => $request->currency,
                    "source" => auth()->user()->stripe_connect_id,
                    'description' => "Wallet Fee for: {$request->description}"
                ]);

                if ($fee->status == 'succeeded') {
                    // Create transaction on behalf of users connected account
                    $charge = \Stripe\Charge::create([
                        "amount" => $request->amount - ($fee_amount >= 50 ? $fee_amount : 50),
                        "currency" => $request->currency,
                        "source" => auth()->user()->stripe_connect_id,
                        'description' => $request->description,
                        'metadata' => [
                            'application_fee' => $fee_amount >= 50 ? $fee_amount : 50
                        ],
                    ], ['stripe_account' => auth()->user()->stripe_connect_id]);

                    // Update transaction
                    $transaction->fill([
                        'stripe_transaction_id' => $charge->id,
                        'status' => $charge->status
                    ]);

                    return $charge;
                }

            case 'card':
            case 'sepa':
            default:
                // If payment method included, confirm intent
                if ($request->payment_method) {
                    $intent = \Stripe\PaymentIntent::retrieve(
                        $request->stripe_transaction_id
                    );
                    $intent->confirm([
                        'payment_method' => $request->payment_method
                    ]);
                }
                $intent = \Stripe\PaymentIntent::retrieve($request->stripe_transaction_id);
                return $intent;
        }
    }
}
