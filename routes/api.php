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
    // login/register no auth routes
    Route::post('register', 'AuthController@register')->name('user.store');
    Route::post('login', 'AuthController@login')->name('user.login');
    Route::post('forgot', 'Auth\ForgotPasswordController@forgot')->name('user.forgot');
    Route::post('reset', 'Auth\ResetPasswordController@reset')->name('user.reset');

    // authorised routes
    Route::middleware(['auth:api', 'user.status'])->group(function () {
        // user routes
        Route::prefix('me')->group(function () {
            Route::get('/', 'AuthController@user')->name('user.show');
            Route::post('fcm/token', 'FcmController@token')->name('user.fcm.token');
            Route::post('fcm/subscribe/{$topic}', 'FcmController@subscribe')->name('user.fcm.subscribe');
            Route::post('fcm/unsubscribe/{$topic}', 'FcmController@unsubscribe')->name('user.fcm.unsubscribe');
            Route::put('update', 'AuthController@update')->name('user.update');
            Route::post('deactivate', 'AuthController@deactivate')->name('user.destroy');
            Route::post('stripe/register', 'StripeController@register')->name('user.stripe.register');
            Route::get('balance', 'StripeController@balance')->name('user.stripe.balance');
            Route::post('withdraw', 'StripeController@withdraw')->name('user.stripe.withdraw');
            Route::post('withdraw/destination', 'StripeController@saveDestination')->name('user.destination.store');
            Route::get('withdraw/destination', 'StripeController@destinations')->name('user.destination.show');
            Route::get('payment/source/prepare', 'StripeController@prepareMethod')->name('user.source.store.prepare');
            Route::get('payment/source', 'StripeController@sources')->name('user.source.show');
            Route::post('payment/source', 'StripeController@saveSource')->name('user.source:store');
        });

        // resource routes
        Route::get('transaction/prepare', 'TransactionController@prepare')->name('transaction.prepare');
        Route::post('transaction/pay', 'TransactionController@pay')->name('transaction.pay');
        Route::apiResource('transaction', 'TransactionController')->only('show', 'destroy');
        Route::apiResource('request', 'PaymentRequestController');
        Route::prefix('budget')->group(function () {
            Route::apiResource('/', 'BudgetListController');
            Route::apiResource('item', 'BudgetItemController');
        });

        // helper routes
        Route::get('search', 'SearchController')->name('search');
        Route::get('fees', 'FeeController')->name('fees');
    });
});
