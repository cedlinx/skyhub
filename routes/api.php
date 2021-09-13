<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//added by coa
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\VerificationController;


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

//Auth::routes(['verify' => true]); //Enables email verification for the authentication routes

Route::middleware(['cors', 'json.response', 'auth:api'])->get('/user', function (Request $request) {
    return $request->user();
});

// public routes
Route::group(['middleware' => ['cors', 'json.response']], function () {
    
    // Standard Authentication
    Route::post('/login', 'Auth\ApiAuthController@login')->name('login.api'); //APiAuthController's login fcn has been Modified to allow this work with email verification as well
    Route::post('/register','Auth\ApiAuthController@register')->name('register.api');

    Route::get('/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->middleware(['signed'])->name('verification.verify');
    Route::post('/resend/email/verification', 'Auth\VerificationController@resend')->middleware(['throttle:6,1'])->name('verification.send');
    
    //FORGOT PASSWORD 
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkResponse'])->name('passwords.sent');
    Route::post('/reset-password', [ResetPasswordController::class, 'sendResetResponse'])->name('passwords.reset');
   
    //SOCIAL LOGIN  //These urls are referenced in config/services.php where the providers are registered. If you change them here, change them there as well
    Route::get('/gosocial', 'Auth\LoginController@show')->name('social.login');
    Route::get('/gosocial/{driver}', 'Auth\LoginController@redirectToProvider')->name('social.oauth');
    Route::get('/gosocial/callback/{driver}', 'Auth\LoginController@handleProviderCallback')->name('social.callback');

    //TESTING SEND EMAIL STANDALONE -- to be used for sending out emails if necessary
    //These sendmail routes are currently not in use, but may be needed later... The functions are not in the specified controller, except in the backed up version
    //Route::get('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  //Works great
    //Route::post('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  

    //NOT required? added to provide the frontend dev the reset password token IF REQUIRED
    Route::get('/password-reset-token', 'Auth\ResetPasswordController@ShowPasswordForm')->name('newpasswordform.api');

    //ASSETS
    Route::get('/assets', 'AssetController@index')->name('assets.api')->middleware('verified'); //Guests should be able to verify assets, that's why this isn't in the protected route below
});

// protected routes will be placed here
Route::middleware('auth:api')->group(function () {    
    Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout.api');

    //Get the password reset form after the reset link is clicked
    Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@getNewPassword')->name('newpassword.api');
 
//Used for Access Control --- see kernel.php for where I defined them
//    Route::get('/assets', 'AssetController@index')->middleware('api.admin')->name('assets.api');
//    Route::get('/assets', 'AssetController@index')->middleware('api.superAdmin')->name('assets.api');
//

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

/*
//From Kenny Starts here
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
//From Kenny Ends here
*/