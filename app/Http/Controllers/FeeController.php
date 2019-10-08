<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FeeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $fees = [];

        switch ($request->type) {
            case 'payment':
                if ($request->sender_country !== $request->recipient_country) {
                    $fees[] = $this->getInternationalFee($request->amount, $request->sender_country);
                }
                $fees[] = $this->getSenderFee($request->amount, $request->sender_country);
                break;

            case 'withdrawl':
                $fees[] = $this->getWithdrawlFee($request->amount, $request->sender_country);
                break;
        }

        // Calculate total
        $total = $request->amount;
        foreach($fees as $fee) {
            $total -= $fee['amount'];
        }
        $fees[] = ['type' => 'total', 'amount' => $total];

        return $fees;
    }

    private function getWithdrawlFee($amount, $country) {
        // API: Stripe sender fees
        $fees = [
            'Australia' => [
                'percent' => 0.005,
                'fixed' => 25
            ],
            'New Zealand' => [
                'percent' => 0.005,
                'fixed' => 25
            ],
            'Singapore' => [
                'percent' => 0.0025,
                'fixed' => 50
            ]
        ];

        // Calculate fee amount
        return [
            'type' => 'withdrawl_fee',
            'amount' => ($fees[$country]['percent'] * $amount) + $fees[$country]['fixed']
        ];
    }

    private function getSenderFee($amount, $country) {
        // API: Stripe sender fees
        $fees = [
            'Australia' => [
                'percent' => 0.0175,
                'fixed' => 30
            ],
            'New Zealand' => [
                'percent' => 0.029,
                'fixed' => 30
            ],
            'Singapore' => [
                'percent' => 0.034,
                'fixed' => 50
            ]
        ];
        $stripe_fee_amount = ($fees[$country]['percent'] * $amount) + $fees[$country]['fixed'];

        // Calculate wallet fees using scale
        switch ($country) {
            case 'Australia':
            case 'New Zealand':
            case 'Singapore':
            default:
                if ($amount <= 10000) $wallet_fee_amount = 50;
                else if ($amount > 10000 && $amount <= 50000) $wallet_fee_amount = 80;
                else if ($amount > 50000 && $amount <= 100000) $wallet_fee_amount = 120;
                else $wallet_fee_amount = 145;
                break;
        }

        return [
            'type' => 'sender_fee',
            'amount' => $wallet_fee_amount + $stripe_fee_amount
        ];
    }

    private function getInternationalFee($amount, $country) {
        // API: Stripe sender fees
        $fees = [
            'Australia' => [
                'percent' => 0.029,
                'fixed' => 30
            ],
            'New Zealand' => [
                'percent' => 0.02,
                'fixed' => 0
            ],
            'Singapore' => [
                'percent' => 0.02,
                'fixed' => 0
            ]
        ];

        // Calculate fee amount
        return [
            'type' => 'international_fee',
            'amount' => ($fees[$country]['percent'] * $amount) + $fees[$country]['fixed']
        ];
    }
}
