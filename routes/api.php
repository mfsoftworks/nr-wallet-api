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

            Route::post('fcm/subscribe/{$topic}', 'FcmController@subscribe')
                ->middleware('scope:update-profile-fcm')
                ->name('user.fcm.subscribe');

            Route::post('fcm/unsubscribe/{$topic}', 'FcmController@unsubscribe')
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

            Route::get('balance', 'StripeController@balance')
                ->middleware('scope:view-balance')
                ->name('user.stripe.balance');

            Route::post('withdraw', 'StripeController@withdraw')
                ->middleware('scope:withdraw-balance')
                ->name('user.stripe.withdraw');

            Route::post('withdraw/destination', 'StripeController@saveDestination')
                ->middleware('scope:create-withdrawl-destination')
                ->name('user.destination.store');

            Route::get('withdraw/destination', 'StripeController@destinations')
                ->middleware('scope:view-withdrawl-destination')
                ->name('user.destination.show');

            Route::get('payment/source/prepare', 'StripeController@prepareMethod')
                ->middleware('scope:create-payment-source')
                ->name('user.source.store.prepare');

            Route::get('payment/source', 'StripeController@sources')
                ->middleware('scope:create-payment-source')
                ->name('user.source.show');

            Route::post('payment/source', 'StripeController@saveSource')
                ->middleware('scope:create-payment-source')
                ->name('user.source:store');
        });

        // resource routes
        Route::get('transaction/prepare', 'TransactionController@prepare')
            ->middleware('scope:create-pending-transaction')
            ->name('transaction.prepare');

        Route::post('transaction/pay', 'TransactionController@pay')
            ->middleware('scope:confirm-transaction')
            ->name('transaction.pay');

        Route::apiResource('transaction', 'TransactionController')
            ->middleware('scope:view-transaction-history')
            ->only('show');

        Route::apiResource('request', 'PaymentRequestController')
            ->middleware('scope:create-payment-request')
            ->only('show', 'store');

        Route::apiResource('budget', 'BudgetListController')
            ->middleware('scope:create-budget-list');

        Route::apiResource('budget/item', 'BudgetItemController')
            ->middleware('scope:create-budget-list');
    });
});
