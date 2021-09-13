<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
            'password' => 'required|string|min:6|confirmed',
            'address' => 'required|string',
            'phone' => 'required|string|min:8|max:12',
            'role' => 'integer',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $request['role'] = $request['role'] ? $request['role']  : 0; //During signup, role is not available to the user so... it may as well just be 0
        
        $user = User::create($request->toArray());
        event(new Registered($user)); //Trigger Email verification. This will call the VerifyEmail function specified in the User model. So you can create your own Email Notification and call it from there. I have one here which I used for testing/debugging and it works great too.
    //    $user->sendEmailVerificationNotification();   //This works great as well BUT it is manual unline using the Registered event

        $token = $user->createToken('Laravel Password Grant Client')->accessToken; //use this to auto-login registered user. They cannot verify their email if they are not authenticated
        $response = [
            'message' => 'Registration was successful! Check your email ('. $request['email'] .') to activate your account',
            'data' => [
                'user_id' => $user->id,
                'name' => $request['name'],
                'email' => $request['email'],
                'token' => $token
            ] 
        ];

        return response($response, 200);
    }

    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {           
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;

                //check whether email has been verified
                if (is_null($user->email_verified_at)) {
                    $response = [
                        'message' => 'Login Successful! Restricted Access! Please verify your email and try again.',
                        'remark' => 'User should be able to request another activation link',
                        'data' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => $user->address,
                            'token' => $token
                        ]
                    ];
                    return response($response, 401);
                }   //end of verification check

                $response = [                    
                    'message' => 'Login was successful!',
                    'logged_in_user' => $user->name,
                    'token' => $token
                ];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }

    
}

