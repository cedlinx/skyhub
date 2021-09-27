<?php

namespace App\Http\Controllers;

use App\Models\Recovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use Validator;

class RecoveryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    //    $recoveries = Recovery::all();
        $recoveries = DB::table('recoveries')
            ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
            ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
            ->select('recoveries.*', 'assets.name as asset-name', 'users.name as founder', 'owners.name as owner-name')
            ->get();

        return response()->json($recoveries, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Recovery  $recovery
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //$request->id is auth()->user()->id
        $recoveries = DB::table('recoveries')
            ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
            ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
            ->select('recoveries.*', 'assets.name as asset-name', 'users.name as founder', 'owners.name as owner-name')
            ->where('recoveries.owner', $request->id)
            ->get();

        return response()->json($recoveries, 200);
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recovery  $recovery
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        
    }

}
