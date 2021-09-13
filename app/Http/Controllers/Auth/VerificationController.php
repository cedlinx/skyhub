<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;

//for implementing email verification here/overriding the default handler
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

use Illuminate\Support\Facades\Validator;
use App\Models\User;


class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */
    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('verify', 'resend');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
    
    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));
    
        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            //throw new AuthorizationException;
            $response = [
                'message' => 'Sorry! You are not authorized to do this... Check your credentials and try again.',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'id' => $user->id
                ]
            ];
            $code = 401;
    
            return response($response, $code);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $response = [
                'message' => 'Thanks! Your email has been verified and you are being redirected',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'id' => $user->id
                ]
            ];
            $code = 200;

        } else {
            $response = [
                'message' => 'Your email is already verified. Thank you',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'id' => $user->id
                ]
            ];
            $code = 200;
        }
    
        return response($response, $code);
    }
    
    public function resend(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $email = $request->only('email');
        $user = User::where('email', $email)->first();
        auth()->login($user);   //Auth::login($user); also works perfectly

        if (auth()->user()->hasVerifiedEmail()) {
            return response()->json(["msg" => "Your email has already been verified."], 400);
        }
    
        auth()->user()->sendEmailVerificationNotification();

        return response()->json(["msg" => "Email verification link sent to your email id"], 200);
    }
}
