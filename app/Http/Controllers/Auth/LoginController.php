<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

//social login
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    //from here down handles social login... above is the default
    protected $providers = [
        'github','facebook','google','twitter', 'apple'
    ];


    
    //not required for API
    public function show()
    {
        //return view('auth.login');
        return response('Select a Social Account to proceed', 200);
    }
    
    public function redirectToProvider($driver)
    {
        if( ! $this->isProviderAllowed($driver) ) {
            //return $this->sendFailedResponse("{$driver} is not currently supported");
            return response($this->sendFailedResponse("{$driver} is not currently supported"), 401);
        }

        try {
            //return Socialite::driver($driver)->redirect();
            $response = Socialite::driver($driver)->redirect();
            return response($response, 200);
        } catch (Exception $e) {
            // Display some simple failure message
            //return $this->sendFailedResponse($e->getMessage());
            return response($this->sendFailedResponse("Oops! Something went wrong and {$driver} could not login"), 400);
        }
    }

  
    public function handleProviderCallback( $driver )
    {
        try {
            $user = Socialite::driver($driver)->user();
        } catch (Exception $e) {
           // return $this->sendFailedResponse($e->getMessage());
            return response($this->sendFailedResponse($e->getMessage()), 400);
        }

        // check for email in returned user
       // return empty( $user->email )
        $response = empty( $user->email )
            ? $this->sendFailedResponse("No email id returned from {$driver} provider.")
            : $this->loginOrCreateAccount($user, $driver);

            return response($response, 200);
    }

    protected function sendSuccessResponse()
    {
        //return redirect()->intended('home');
        $response = redirect()->intended('home');
        return response($response, 200);
    }

    protected function sendFailedResponse($msg = null)
    {
        return redirect()->route('social.login')
            ->withErrors(['msg' => $msg ?: 'Unable to login, try with another provider to login.']);
    }

    protected function loginOrCreateAccount($providerUser, $driver)
    {
        // check whether use already has an account
        $user = User::where('email', $providerUser->getEmail())->first();

        // if user already exists
        if( $user ) {
            // update the avatar and provider that may have changed
            $user->update([
                'avatar' => $providerUser->avatar,
                'provider' => $driver,
                'provider_id' => $providerUser->id,
                'access_token' => $providerUser->token
            ]);
        } else {
            // create a new user
            if($providerUser->getEmail()){ //Check whether email exists or not. If it exists create a new user: REQUIRED for social accounts like Facebook that were opened WITHOUT an email (but phone number)
                $user = User::create([
                    'name' => $providerUser->getName(),
                    'email' => $providerUser->getEmail(),
                    'avatar' => $providerUser->getAvatar(),
                    'provider' => $driver,
                    'provider_id' => $providerUser->getId(),
                    'access_token' => $providerUser->token,
                    'address' => $providerUser->getAddress(), //verify existence of getAddress() method
                    'phone' => $providerUser->getPhone(), //verify existence of getPhone() method
                    // user can use reset password to create a password
                    //CONSIDER triggering RESET Password
                    'password' => ''
                ]);
            }else{
            
                $response = ['error'=>'An email address is not available on this social account', 'message'=>'Sorry, you cannot login with this account! Kindly try another Social account.'];
                return response($response, 200);
            }
        }

        // login the user
        Auth::login($user, true);

        return $this->sendSuccessResponse();
    }

    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }
}
