<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

//Email verification
use Illuminate\Auth\Events\Registered;    //This is automatically implemented if you use a Laravel start-kit (like Jetstream)
use Illuminate\Support\Facades\Auth;

class ApiAuthController extends Controller
{
    //
    public function register (Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'required|string',
            'phone' => 'required|string|min:8|max:12',
        //    'role' => 'nullable|integer',     //moved, so superadmins can assign roles to their team members
        //    'group_id' => 'nullable|integer', //moved, so during company registration, users can specify whether they are an Agency or a Business Enterprise... all other users are Individual by default
            'pin' => 'required|digits:4',
            'sospin' => 'nullable|digits:4',
            'company_code' => 'nullable|string'
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        if ( !( is_null($request['company_code']) ) ) {
            $company = Company::where('code', $request->company_code)->first(); 
            if( !($company) ) return response()->json(['Company Code not found! Please try again'], 422);
            $request['company_id'] = $company->id;
        }

        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $request['role_id'] = $request['role_id'] ? $request['role_id']  : 0; //During signup, role is not available to the user so... it may as well just be 0
        
        $user = User::create($request->toArray());
        event(new Registered($user)); //Trigger Email verification. This will call the VerifyEmail function specified in the User model. So you can create your own Email Notification and call it from there. I have one here which I used for testing/debugging and it works great too.
    //    $user->sendEmailVerificationNotification();   //This works great as well BUT it is manual unline using the Registered event

        //next line removed on Olamide's request
        $token = $user->createToken('Laravel Password Grant Client')->accessToken; //use this to auto-login registered user. They cannot verify their email if they are not authenticated
        $response = [
            'message' => 'Registration was successful! Check your email ('. $request['email'] .') to activate your account',
            'data' => [
                'user_id' => $user->id,
                'name' => $request['name'],
                'email' => $request['email'],
                'token' => $token,
                'verified' => is_null($user->email_verified_at) ? false : true 
            ] 
        ];

        return response()->json($response, 200);
    }

    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {           
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;

                //check whether email has been verified
                if (is_null($user->email_verified_at)) {
                    $response = [
                        'message' => 'Please verify your email and try again.',
                        'remark' => 'User should be able to request another activation link',
                        'data' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => $user->address,
                            'token' => $token, //we are /*NO LONGER*/ preventing login by unverified emails
                            'verified' => is_null($user->email_verified_at) ? false : true 
                        ]
                    ];
                    return response()->json($response, 401);
                }   //end of verification check

                $response = [                    
                    'message' => 'Login was successful!',
                    'logged_in_user' => $user->name,
                    'token' => $token,
                    'verified' => is_null($user->email_verified_at) ? false : true 
                ];
                return response()->json($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response()->json($response, 412);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response()->json($response, 400);
        }
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response()->json($response, 200);
    }

    //Handle MethodNotAllowedHttpException
    public function noGet(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using GET instead of POST!"
        ];

        return response()->json($response, 405);
    }

    public function noPost(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using POST instead of GET!"
        ];

        return response()->json($response, 405);
    }
}

