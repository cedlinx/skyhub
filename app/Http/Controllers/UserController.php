<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Arr;
use Validator;


class UserController extends Controller
{
    public function danger($email){
        $user = User::where('email', $email)->first();

        if (! ($user) ) {
            return response()->json(["message" => "User not found!"]);
        }
        $newMail = $user->id.$email;
        $user->update(['email' => $newMail]);
        return response()->json(["message"=>"Delete Successful!"], 200);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $allUsers = null;
        User::chunkById(2000, function($users) use(&$allUsers){         
            $allUsers = $users;
        });

        if ( is_null($allUsers) ) {
            return response()->json([
                'success' => false,
                'data' => 'No user record found!'
             ]);
        } else {
        
            return response()->json([
                'success' => true,
                'data' => $allUsers
            ]);
        }
    /*    //This works but may have performance issues when the database get really large
       $users = User::all();
       if( ! ($users) )
            return response()->json([
                'success' => true,
                'message' => 'User record is empty!'
            ]);

       return response()->json([
           'success' => true,
           'data' => $users
        ]);
    */
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //This is handled in the ApiAuthController
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //used for /find/user
        $ref = $request->user;
        $user = User::where('email', $ref)->orWhere('id', $ref)->first();
        if ( ! ($user) ) return response()->json(['Sorry! User not found.'], 404);
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
 
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $id = $request->id;
        $user = User::find($id);
 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! User could not be found!'
            ], 400);
        }
 
        $updated = $user->fill($request->all())->save(); 
        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'User details have been updated.'
            ], 200);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $ref = $request->user;
        $user = User::where('email', $ref)->orWhere('id', $ref)->first();

        if (! ($user) ) {
            return response()->json(["message" => "User not found!"]);
        }
        $user->delete();
        return response()->json(["message"=>"Delete Successful!"], 200);
    }

    public function destroySelf(Request $request)
    {
        //GDPR Compliance
        //Allow users to delete their user data... this includes their asset data, transaction history and all?
        $id = $request->id;
        $user = User::find($id);
        auth()->logout($user);
                       // $user = User::where('email', $request->email)->first();
                       // auth()->login($user);
        //$user = User::find(auth()->user()->id);
        $user->delete();
        //Add code to delete all related data (assets)  //cannot delete related transfers and recoveries
        foreach($user->assets as $asset) {
         // $user->assets()->delete();   //works for bulk deleting user's assets
            $txnID = $this->setValidity($asset->id, false);
            $asset->deletion_txn_id = $txnID;
            $asset->save();
            $asset->delete();
        }

        return response()->json([
            "success" => true,
            "message" => "Your account has been deleted! And you have been logged out. It is sad to see you go. We hope you signup again soon."
        ]);
    }
}
