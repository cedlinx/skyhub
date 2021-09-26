<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index()
    {
        //    $transfers = Recovery::all();
        $transfers = DB::table('transfers')
            ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
            ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
            ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
            ->select('transfers.*', 'assets.name as asset-name', 'users.name as previous-owner', 'newOwners.name as new-owner')
            ->get();

        return response()->json($transfers, 200);
    }

    public function show(Request $request)
    {
        //$request->id is auth()->user()->id
        $transfers = DB::table('transfers')
            ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
            ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
            ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
            ->select('transfers.*', 'assets.name as asset-name', 'users.name as previous-owner', 'newOwners.name as new-owner')
            ->where('transfers.user_id', $request->id)
            ->get();

        return response()->json($transfers, 200);
    }

    public function get_transfer_history()
    {
        //Build an asset's transfer history beginning from the very first owner to the current owner

    }
}
