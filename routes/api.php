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
//use App\Http\Controllers\AssetController;


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

// public routes ... available to guests / unauthenticated users
Route::group(['middleware' => ['cors', 'json.response']], function () {

    //MUST DELETE 
    Route::get('/testing/delete/user/{email}','UserController@danger')->name('danger.api');

    // Standard Authentication
    Route::post('/login', 'Auth\ApiAuthController@login')->name('login.api'); //APiAuthController's login fcn has been Modified to allow this work with email verification as well
    Route::post('/register','Auth\ApiAuthController@register')->name('register.api');
    
    Route::get('/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->middleware(['signed'])->name('verification.verify');
    Route::post('/resend/email/verification', 'Auth\VerificationController@resend')->middleware(['throttle:6,1'])->name('verification.send');
    
    //FORGOT PASSWORD 
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkResponse'])->name('passwords.sent');
    Route::post('/reset-password', [ResetPasswordController::class, 'sendResetResponse'])->name('passwords.reset');
   
    //SOCIAL LOGIN  //These urls are referenced in config/services.php where the providers are registered. If you change them here, change them there as well
    //Route::get('/gosocial', 'Auth\LoginController@show')->name('social.login');   May be required if we were creating Views... displays the list of social login providers
    //Route::get('/gosocial/{driver}', 'Auth\LoginController@redirectToProvider')->name('social.oauth');    May be required if we were creating Views... redirects to the social login providers when their icon/link is clicked
    Route::get('/gosocial/callback/{driver}', 'Auth\LoginController@handleProviderCallback')->name('social.callback');

    //TESTING SEND EMAIL STANDALONE -- to be used for sending out emails if necessary
    //These sendmail routes are currently not in use, but may be needed later... The functions are not in the specified controller, except in the backed up version
    //Route::get('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  //Works great
    //Route::post('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  

    //NOT required? added to provide the frontend dev the reset password token IF REQUIRED
    Route::get('/password-reset-token', 'Auth\ResetPasswordController@ShowPasswordForm')->name('newpasswordform.api');

    //ASSETS
    //Query for a specific asset ... available to the pulic
    //Route::post('/find/asset/{ref}', 'AssetController@show')->middleware(['sanitize', 'log.route']);  //ref is either skydahid or assetid
    Route::post('/find/asset', 'AssetController@show')->middleware(['sanitize', 'log.route']);  //ref is either skydahid or assetid
    //Route::get('/assets', 'AssetController@index')->name('assets.api')->middleware('verified'); //Guests should be able to verify assets, that's why this isn't in the protected route below

    //METHODS NOT ALLOWED HTTP EXCEPTION HANDLING
    Route::get('/register','Auth\ApiAuthController@noGet')->name('register.api');
    Route::get('/login', 'Auth\ApiAuthController@noGet')->name('login.api');
    Route::get('/logout', 'Auth\ApiAuthController@noGet')->name('logout.api');
    Route::post('/reset-password/{token}', 'Auth\ResetPasswordController@noPost')->name('newpassword.api');
    Route::get('/find/asset/{ref}', 'Auth\ApiAuthController@noGet')->name('assets.api');    //->middleware('verified');
    Route::post('/email/verify/{id}/{hash}', 'Auth\VerificationController@noPost')->middleware(['signed'])->name('verification.verify');
    Route::get('/resend/email/verification', 'Auth\VerificationController@noGet')->middleware(['throttle:6,1'])->name('verification.send');
    
    Route::get('/forgot-password', 'Auth\ApiAuthController@noGet')->name('passwords.sent');
    Route::get('/reset-password', 'Auth\ApiAuthController@noGet')->name('passwords.reset');
    Route::post('/password-reset-token', 'Auth\VerificationController@noPost')->name('newpasswordform.api');
    Route::post('/gosocial', 'Auth\VerificationController@noPost')->name('social.login');
    Route::post('/gosocial/{driver}', 'Auth\VerificationController@noPost')->name('social.oauth');
    Route::post('/gosocial/callback/{driver}', 'Auth\VerificationController@noPost')->name('social.callback');
});

// protected routes will be placed here
//Route::middleware(['cors', 'json.response'])->group(function () {  
Route::middleware(['cors', 'json.response', 'auth:api'])->group(function () {    
    Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout.api');

    //USER MANAGEMENT ROUTES        //INCLUDE ROUTES FOR STATS
    //MAKE DOUBLE SURE BEFORE ALLOWING A USER TO DO THIS
    Route::post('/delete/account', 'UserController@destroySelf');   //deletes own account
    Route::get('/delete/account', 'Auth\ApiAuthController@noGet');
    Route::post('/delete/user', 'UserController@destroy');          //deletes another user (superAdmin)
    Route::get('/delete/user', 'Auth\ApiAuthController@noGet');
    Route::post('/find/user', 'UserController@show');          
    Route::get('/find/user', 'Auth\ApiAuthController@noGet');
    Route::post('/modify/user', 'UserController@update');          
    Route::get('/modify/user', 'Auth\ApiAuthController@noGet');
    Route::get('/list/users', 'UserController@index')->name('list.users')->middleware(['api.superAdmin']);
    Route::post('/list/users', 'Auth\VerificationController@noPost');

        //Get the password reset form after the reset link is clicked
        Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@getNewPassword')->name('newpassword.api');
    
    //Used for Access Control --- see kernel.php for where I defined them
    //    Route::get('/assets', 'AssetController@index')->middleware('api.admin')->name('assets.api');
    //    Route::get('/assets', 'AssetController@index')->middleware('api.superAdmin')->name('assets.api');
    //

    Route::prefix('asset')->middleware('log.route')->group(function () {
        Route::post('/add', 'AssetController@add_asset');
        Route::post('/generate_company_codes', 'AssetController@generate_company_codes')->middleware(['enterprise', 'api.admin', 'api.superAdmin']);
        Route::get('/get_company_codes/{id}', 'AssetController@get_company_codes')->middleware(['enterprise', 'api.admin', 'api.superAdmin']);
        Route::post('/upload_bulk_assets', 'AssetController@upload_bulk_assets');

        //METHODS NOT ALLOWED HTTP EXCEPTION HANDLING
        Route::get('/add', 'Auth\ApiAuthController@noGet');
        Route::get('/generate_company_codes', 'Auth\ApiAuthController@noGet');
        Route::post('/get_company_codes/{id}', 'Auth\ApiAuthController@noPost');
        Route::get('/upload_bulk_assets', 'Auth\ApiAuthController@noGet');

        //COA routes:a
        
        Route::get('/list', 'AssetController@index');
        Route::post('/list', 'Auth\ApiAuthController@noPost');
        Route::post('/modify', 'AssetController@update');
        Route::get('/modify', 'Auth\ApiAuthController@noGet');
        Route::post('/delete', 'AssetController@destroy');
        Route::get('/delete', 'Auth\ApiAuthController@noGet');
        Route::post('/transfer', 'AssetController@transfer');
        Route::get('/transfer', 'Auth\ApiAuthController@noGet');
        Route::post('/confirm/transfer', 'AssetController@transfer');   //Owner responds YES when alerted of attempt to register asset
        Route::get('/confirm/transfer', 'Auth\ApiAuthController@noGet');
        Route::post('/decline/transfer', 'AssetController@transfer');   //Owner responds LOST when alerted of attempt to register asset
        Route::get('/decline/transfer', 'Auth\ApiAuthController@noGet');
     //    Route::post('/add/document', 'AssetController@uploadFile');
     //    Route::get('/add/document', 'Auth\ApiAuthController@noGet');
        Route::post('/flag/lost', 'AssetController@flagAssetAsMissing');
        Route::get('/flag/lost', 'Auth\ApiAuthController@noGet');
        Route::post('/flag/found', 'AssetController@flagAssetAsFound');
        Route::get('/flag/found', 'Auth\ApiAuthController@noGet');
        Route::get('/list/missing', 'AssetController@listMissingAssets');
        Route::post('/list/missing', 'Auth\ApiAuthController@noPost');
        Route::get('/types', 'TypeController@index')->middleware(['api.admin', 'api.superAdmin']);
        Route::post('/types', 'Auth\ApiAuthController@noPost');
        Route::post('/add/type', 'TypeController@store');
        Route::get('/add/type', 'Auth\ApiAuthController@noGet');
        Route::post('/edit/type', 'TypeController@update');
        Route::get('/edit/type', 'Auth\ApiAuthController@noGet');
        Route::post('/delete/type', 'TypeController@destroy');
        Route::get('/delete/type', 'Auth\ApiAuthController@noGet');
        Route::post('/recoveries', 'RecoveryController@show');
        Route::get('/recoveries', 'Auth\ApiAuthController@noGet');
        Route::post('/transfers', 'TransferController@show');
        Route::get('/transfers', 'Auth\ApiAuthController@noGet');
        //COA routes:z
        
    });

    Route::middleware('log.route')->group(function () {
        Route::get('/user/groups', 'GroupController@index');
        Route::post('/user/groups', 'Auth\ApiAuthController@noPost');

        Route::prefix('email')->group(function () {
            Route::post('/send_email', 'EmailServiceController@send_email');
            Route::get('/send_email', 'Auth\ApiAuthController@noGet');
        });

        Route::prefix('sms')->group(function () {
            Route::post('/send_sms', 'SmsServiceController@send_user_sms');
            Route::get('/send_sms', 'Auth\ApiAuthController@noGet');
        });

        Route::prefix('payment')->group(function () {
            Route::post('/save_payment', 'PaymentController@save_payment');
            Route::get('/save_payment', 'Auth\ApiAuthController@noGet');
        });
        Route::middleware(['api.superAdmin', 'api.admin'])->group(function () {
            Route::post('/add/user/group', 'GroupController@store');
            Route::get('/add/user/group', 'Auth\ApiAuthController@noGet');
            Route::post('/edit/user/group', 'GroupController@update');
            Route::get('/edit/user/group', 'Auth\ApiAuthController@noGet');
            Route::post('/delete/user/group', 'GroupController@destroy');
            Route::get('/delete/user/group', 'Auth\ApiAuthController@noGet');
            Route::Get('/asset/recovery/list', 'RecoveryController@index')->middleware('agency');
            Route::post('/asset/recovery/list', 'Auth\ApiAuthController@noPost');
            Route::Get('/asset/transfer/list', 'TransferController@index');
            Route::post('/asset/transfer/list', 'Auth\ApiAuthController@noPost');
        });
    });
});



