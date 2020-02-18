<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', 'FrontendController@welcome');

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/clients', 'FrontendController@clients');
});

Auth::routes();
Route::stripeWebhooks('webhooks/stripe/{configKey}');
