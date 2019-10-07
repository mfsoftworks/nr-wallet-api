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
        // Get request variables
        $amount = $request->amount;
        $country = $request->sender_country;
        $type = $request->type;
        $recipient_country = isset($request->recipient_country) ? $request->recipient_country : null;
        $fees = [];

        switch ($type) {
            case 'payment':
                if ($country !== $recipient_country) $fees[] = getInternationalFee($amount, $country);
                $fees[] = getSenderFee($amount, $country);
                break;

            case 'withdrawl':
                $fees[] = getWithdrawlFee($amount, $country);
                break;
        }

        return $fees;
    }

    private function getWithdrawlFee($amount, $country) {
        // API: Stripe sender fees
        $fee_percents = [
            'Australia' => 0.005,
            'New Zealand' => 0.005,
            'Singapore' => 0.0025
        ];
        $fee_amounts = [
            'Australia' => 0.25,
            'New Zealand' => 0.25,
            'Singapore' => 0.50
        ];

        // Calculate fee amount
        return [
            'type' => 'withdrawl_fee',
            'amount' => ($fee_percents[$country] * $amount) + $fee_amounts[$country]
        ];
    }

    private function getSenderFee($amount, $country) {
        // API: Stripe sender fees
        $fee_percents = [
            'Australia' => 0.0175,
            'New Zealand' => 0.029,
            'Singapore' => 0.034
        ];
        $fee_amounts = [
            'Australia' => 0.30,
            'New Zealand' => 0.30,
            'Singapore' => 0.50
        ];

        // Calculate fee amounts
        $stripe_fee_amount = ($fee_percents[$country] * $amount) + $fee_amounts[$country];
        $wallet_fee_amount = (0.05 - $fee_percents[$country]) * $amount;

        return [
            'type' => 'sender_fee',
            'amount' => $stripe_fee_amount + $wallet_fee_amount
        ];
    }

    private function getInternationalFee($amount, $country) {
        // API: Stripe sender fees
        $fee_percents = [
            'Australia' => 0.029,
            'New Zealand' => 0.02,
            'Singapore' => 0.02
        ];
        $fee_amounts = [
            'Australia' => 0.30,
            'New Zealand' => 0,
            'Singapore' => 0
        ];

        // Calculate fee amount
        return [
            'type' => 'international_fee',
            'amount' => ($fee_percents[$country] * $amount) + $fee_amounts[$country]
        ];
    }
}
