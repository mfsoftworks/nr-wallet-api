<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        Passport::tokensCan([
            'profile-info' => 'View account info (Username, email, display name)',
            'update-profile' => 'Update profile info',
            'update-profile-fcm' => 'Update notification subscriptions',
            'stripe-register' => 'Associate a Stripe account with this profile',
            'view-balance' => 'View accounts pending and available balance',
            'withdraw-balance' => '<b>** SENSITIVE **</b> Withdraw account balance to attached withdrawl destination',
            'create-withdrawl-destination' => '<b>** SENSITIVE **</b> Attach a destination to withdraw account balance to (Bank account or credit/debit card)',
            'view-withdrawl-destinations' => '<b>** SENSITIVE **</b> View attached withdrawl destinations for account (This doesn\'t include sensitive information',
            'create-payment-source' => 'Save new payment source for account',
            'create-pending-transaction' => 'Create new pending transactions for account',
            'confirm-transaction' => '<b>** SENSITIVE **</b> Confirm pending transaction (This deducts money from account and initiates transfer)',
            'view-transaction-history' => 'View account transaction history',
            'create-payment-request' => 'Create a payment request to another user',
            'create-budget-list' => 'Create, update, and delete budget lists'
        ]);

        Passport::setDefaultScope(['profile-info']);
    }
}
