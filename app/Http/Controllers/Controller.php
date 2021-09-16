<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use AfricasTalking\SDK\AfricasTalking;
use xtype\Eos\Client;   //blockchain
use Illuminate\Http\Request;    //blockchain
use Illuminate\Support\Facades\Http; //blockchain

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function sendSuccess($message, $data)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'success' => true
        ], 201);
    }

    public function sendError($message, $data)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'success' => false
        ], 401);
    }

    public function sendSMS($recipients, $message)
    {
        // Set the app credentials
        $username   = env('AFRICASTALKING_USERNAME');
        $apiKey     = env('AFRICASTALKING_APIKEY');

        // Initialize the SDK
        $AT = new AfricasTalking($username, $apiKey);

        // Get the SMS service
        $sms = $AT->sms();

        // Set your shortCode or senderId
        // $from       = "AFRICASTKNG";

        try {
            $result = $sms->send([
                'to'      => $recipients,
                'message' => $message,
                // 'from'    => $from
            ]);

            print_r($result);
        } catch (Exception $e) {
            echo "Error: ".$e->getMessage();
        }
    }

    //Blockchain Integration: lifted from original file (SkydahController.php) to allow for easy calling from Asset Controller and avoid using routes in my controller
    //Blochchain starts

   /**
   * All param data are hard coded. Please be advised to use the Request object to pass in this parameters instead
   * Handle Exceptions as well comming from the blockchain assertions if you may encounter
   *
  * */

  public function createAsset(array $data){  //(Request $request){
    $client = new Client(env('TESTNET_URL'));

    // set your private key
    $client->addPrivateKeys([
        env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
    ]);

    $tx = $client->transaction([
        'actions' => [
            [
                'account' => env('TESTNET_ACCOUNT_NAME'),
                'name' => 'createasset',
                'authorization' => [[
                    'actor' => env('TESTNET_ACCOUNT_NAME'),
                    'permission' => 'active',
                ]],
                'data' => $data
            ]
        ]
    ]);
    //echo "Transaction ID: {$tx->transaction_id}"; //recommended - strore in db as well
    return $tx->transaction_id;
  } 

  public function setValidity(int $asset, bool $val = false){    //(Request $request){
    $client = new Client(env('TESTNET_URL'));

    // set your private key
    $client->addPrivateKeys([
        env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
    ]);

    $tx = $client->transaction([
        'actions' => [
            [
                'account' => env('TESTNET_ACCOUNT_NAME'),
                'name' => 'setvalidity',
                'authorization' => [[
                    'actor' => env('TESTNET_ACCOUNT_NAME'),
                    'permission' => 'active',
                ]],
                'data' => [
                    'asset_id' => $asset,   //1, //primary key from pgsql/mysql database
                    'asset_validity' => $val,   //true, //Asset validity refers to the state of this asset. Either a true asset or false asset and carries a boolean value
                ],
            ]
        ]
    ]);
    //echo "Transaction ID: {$tx->transaction_id}"; //recommended - strore in db as well
    return $tx->transaction_id;
  }

  public function transferAsset(int $id, int $newOwnerID){
    //Set the url - testnet or mainnet
    $client = new Client(env('TESTNET_URL'));

    // set your active private key
    $client->addPrivateKeys([
        env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
    ]);

    $tx = $client->transaction([
        'actions' => [
            [
                'account' => env('TESTNET_ACCOUNT_NAME'), //set the testnet/mainet account name
                'name' => 'transasset',
                'authorization' => [[
                    'actor' => env('TESTNET_ACCOUNT_NAME'),
                    'permission' => 'active',
                ]],
                'data' => [
                  'asset_id' => $id,    //1, //primary key from pgsql/mysql database
                  'asset_skydah_owner' => $newOwnerID,    //'Obinnah', //Asset new owner name or identity
              ],
            ]
        ]
    ]);
    //echo "Transaction ID: {$tx->transaction_id}"; //Store in db
    return $tx->transaction_id;
  }


//the following contain bug fixes for asset retrieval which (above) do not return A SINGLE ASSET
//This is NOT in use as I've used getAssetBySkydahID for both assetid and skydahid
public function getAssetById(int $upper, int $lower){ //(Request $request){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "2", //primary key uint64_t - set ass db id when calling createasset action on the blockchain
       "lower_bound"=> $lower,  //"1", // representation of lower bound value of key
       "upper_bound"=> $upper,  //"4", // representation of upper bound value of key
       "limit"=> 10, //max number of rows to return per query
       "key_type"=> "uint64", //key type of --index_position
       "index_position"=> "1", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode_type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show_payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }

//This is NOT in use as I've used getAssetBySkydahID for both assetid and skydahid
  public function getAssetByHash(string $upper = "", string $lower = ""){ //(Request $request){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", //primary key uint64_t - set as db id when calling createasset action on the blockchain
       "lower_bound"=> $lower,  //"017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", // representation of lower bound value of key
       "upper_bound"=> $upper,  //"017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", // representation of upper bound value of key
       "limit"=> 3, //max number of rows to return per query
       "key_type"=> "sha256", //key type of --index_position. uint64_t or checksum256 for skydak
       "index_position"=> "2", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode-type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show-payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }

  public function  getAssetBySkydahID(string $upper = "", string $lower = ""){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "9402dc14436a3e3983a335e0aca206860858373b1dfe57c0d5c8f50808a14326", //primary key uint64_t - set as db id when calling createasset action on the blockchain
       "lower_bound"=> $lower,  //"9402dc14436a3e3983a335e0aca206860858373b1dfe57c0d5c8f50808a14326", // representation of lower bound value of key
       "upper_bound"=> $upper,  //"9402dc14436a3e3983a335e0aca206860858373b1dfe57c0d5c8f50808a14326", // representation of upper bound value of key
       "limit"=> 1000000000, //max number of rows to return per query
       "key_type"=> "sha256", //key type of --index_position. uint64_t or checksum256 for skydak
       "index_position"=> "3", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode-type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show-payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }

  public function getAssetByOwner($userId){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35", //primary key uint64_t - set as db id when calling createasset action on the blockchain
       "lower_bound"=> "",  //"d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35", // representation of lower bound value of key
       "upper_bound"=> "",  //"d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35", // representation of upper bound value of key
       "limit"=> 1000000000, //max number of rows to return per query
       "key_type"=> "sha256", //key type of --index_position. uint64_t or checksum256 for skydak
       "index_position"=> "4", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode-type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show-payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }
    //Blockchain ends
}
