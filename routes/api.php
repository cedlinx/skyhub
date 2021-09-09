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

Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::prefix('asset')->group(function () {
        Route::post('/add', 'AssetController@add_asset')->middleware('log.route');
        Route::post('/generate_company_codes', 'AssetController@generate_company_codes')->middleware('log.route');
        Route::get('/get_company_codes/{id}', 'AssetController@get_company_codes');
        Route::post('/upload_bulk_assets', 'AssetController@upload_bulk_assets')->middleware('log.route');
    });

    Route::prefix('email')->group(function () {
        Route::post('/send_email', 'EmailServiceController@send_email');
    });

    Route::prefix('sms')->group(function () {
        Route::post('/send_sms', 'SmsServiceController@send_user_sms');
    });

    Route::prefix('payment')->group(function () {
        Route::post('/save_payment', 'PaymentController@save_payment')->middleware('log.route');
    });
});
