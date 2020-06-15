<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API: v1 routes
Route::prefix('v1')->group(function () {
    // no auth routes
    Route::post('register', 'AuthController@register')
        ->name('user.store');

    Route::post('login', 'AuthController@login')
        ->name('user.login');

    Route::post('forgot', 'Auth\ForgotPasswordController@forgot')
        ->name('user.forgot');

    Route::post('reset', 'Auth\ResetPasswordController@reset')
        ->name('user.reset');

    // helper routes
    Route::get('search', 'SearchController')
        ->name('search');

    Route::get('fees', 'FeeController')
        ->name('fees');

    // resource routes
    Route::get('profile/{name}', 'ProfileController@showUsername')
        ->where('name', '[A-Za-z]+[0-9]*')
        ->name('profile.name.show');

    Route::get('profile/{id}', 'ProfileController@show')
        ->where('id', '[0-9]+')
        ->name('profile.show');

    Route::get('profile/stripe/{id}', 'StripeController@basicAccount')
        ->name('profile.stripe');

    Route::get('transaction/prepare', 'TransactionController@prepare')
        ->middleware('scope:create-pending-transaction', 'nonce:transaction')
        ->name('transaction.prepare');

    Route::put('transaction/update', 'TransactionController@update')
        ->middleware('scope:create-pending-transaction')
        ->name('transaction.update');

    Route::post('transaction/pay', 'TransactionController@pay')
        ->middleware('scope:confirm-transaction')
        ->name('transaction.pay');

    Route::get('transaction/intent/{id}', 'TransactionController@showIntent')
        ->middleware('scope:view-transaction-history')
        ->name('transaction.intent.show');

    Route::apiResource('transaction', 'TransactionController')
        ->middleware('scope:view-transaction-history')
        ->only('show', 'index');

    // authorised routes
    Route::middleware(['auth:api', 'user.status'])->group(function () {
        // user routes
        Route::prefix('me')->group(function () {
            Route::get('/', 'AuthController@user')
                ->middleware('scope:profile-info')
                ->name('user.show');

            Route::post('fcm/token', 'FcmController@token')
                ->middleware('scope:update-profile-fcm')
                ->name('user.fcm.token');

            Route::delete('fcm/token/{token}', 'FcmController@remove')
                ->middleware('scope:update-profile-fcm')
                ->name('user.fcm.token.delete');

            Route::post('fcm/subscribe/{topic}', 'FcmController@subscribe')
                ->middleware('scope:update-profile-fcm')
                ->name('user.fcm.subscribe');

            Route::post('fcm/unsubscribe/{topic}', 'FcmController@unsubscribe')
                ->middleware('scope:update-profile-fcm')
                ->name('user.fcm.unsubscribe');

            Route::put('update', 'AuthController@update')
                ->middleware('scope:update-profile')
                ->name('user.update');

            Route::post('deactivate', 'AuthController@deactivate')
                ->middleware('scope:update-profile')
                ->name('user.destroy');

            Route::post('stripe/register', 'StripeController@register')
                ->middleware('scope:stripe-register')
                ->name('user.stripe.register');

            Route::get('stripe/dashboard', 'StripeController@loginLink')
                ->middleware('scope:stripe-register')
                ->name('user.stripe.register');

            Route::get('stripe/account', 'StripeController@account')
                ->middleware('scope:stripe-register')
                ->name('user.stripe.register');

            Route::get('balance', 'StripeController@balance')
                ->middleware('scope:view-balance')
                ->name('user.stripe.balance');

            Route::post('withdrawl', 'StripeController@withdraw')
                ->middleware('scope:withdraw-balance')
                ->name('user.stripe.withdraw');

            Route::get('withdrawl', 'StripeController@listWithdraw')
                ->middleware('scope:withdraw-balance')
                ->name('user.stripe.withdraw');

            Route::get('withdraw/destination', 'StripeController@destinations')
                ->middleware('scope:view-withdrawl-destination')
                ->name('user.destination.show');

            // Route::post('withdraw/destination', 'StripeController@saveDestination')
            //     ->middleware('scope:create-withdrawl-destination')
            //     ->name('user.destination.store');

            Route::get('payment/source', 'StripeController@sources')
                ->middleware('scope:show-payment-source')
                ->name('user.source.show');

            Route::get('payment/source/{method}', 'StripeController@source')
                ->middleware('scope:show-payment-source')
                ->name('user.source.show');

            Route::post('payment/source', 'StripeController@saveSource')
                ->middleware('scope:create-payment-source')
                ->name('user.source.store');

            Route::delete('payment/source/{method}', 'StripeController@deleteSource')
                ->middleware('scope:delete-payment-source')
                ->name('user.source.delete');
        });

        // resource routes
        Route::apiResource('request', 'PaymentRequestController')
            ->middleware('scope:create-payment-request')
            ->only('show', 'store');

        Route::apiResource('budget', 'BudgetListController')
            ->middleware('scope:create-budget-list');

        Route::apiResource('budget/item', 'BudgetItemController')
            ->middleware('scope:create-budget-list');
    });
});
