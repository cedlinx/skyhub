<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Asset;
use App\Models\CompanyCode;
use Mail;
use App\Mail\MyTestMail;

//File Hashing and Uploads
//use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr; //used in the show() method to add blockchain data to local data
use App\Image;
use App\Filename;
use Storage;

use App\Models\User;
use App\Models\Recovery;
use App\Models\Transfer;

                                                        //NOTE: Change type_id in DB to category_id via migration

class AssetController extends Controller
{
    //REMOVE THIS CONSTRUCT IN PRODUCTION
    public function __construct()
    {
        if ( ! auth()->user()){
            $user = User::where('email', 'cedlinx@yahoo.com')->first();
            auth()->login($user);
        //    $this->actingAs($user, 'api');
        }

    }
/*
    public function getTestUser()
    {
    //    $user = User::where('email', 'cedlinx@yahoo.com')->first();
    //    auth()->login($user);
     //   return $user;
    }
*/
    public function index()
    {
        //Retrieve and display all assets belonging to the logged in user
    //    $user = $this->getTestUser();
        $assets = auth()->user()->assets;   //->asset;

        //retried auth user's assets from the blockchain
        $bcAssets = $this->getAssetByOwner(auth()->user()->id);
 
        //merge localdata and blockchain data
        $assets = Arr::add($assets, 'blockchain', $bcAssets);
        return response()->json([
            'success' => true,
            'data' => $assets
        ], 200);
    }

    //public function show($ref)    //works for a GET
    public function show(Request $request)
    {   
        //Query Skydah for a specific asset using either the assetid or the skydahid
        //$asset = Asset::where('skydahid', $ref)->orWhere('assetid', $ref)->first();
        $asset = Asset::where('skydahid', $request->skydahid)->orWhere('assetid', $request->assetid)->first();
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found! Please secure your asset by registering it on Skydah.'
            ], 400);
        }
        
        //retrieve asset from blockchain
        $bcAsset = hash("sha256", $asset->skydahid);    //whether the user specified assetid or skydahid, skydahid will be used to query the blockchain because it is most likely to always be unique
        $res = $this->getAssetBySkydahId($bcAsset, $bcAsset);   //array
        $asset = $asset->toArray();

        $asset = Arr::add($asset, 'blockchain', $res);
        return response()->json([
            'success' => true,
            'data' => $asset    //->toArray()
        ], 200);
    }

    public function update(Request $request)
    {
//$user = $this->getTestUser();
        $id = $request->id;
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! Asset could not be found!'
            ], 400);
        }
 
        $updated = $asset->fill($request->all())->save();   
 
        if ($updated)
        {
            $updateTxn = $this->setValidity($id, false);
            $asset = $asset->fresh(); //retrieve updated data

            if ($asset->delete()) { //delete the record and recreate it... UNNECESSARY BUT blockchain recreation would have ID conflict
                //update txnID on our database
                $asset->deletion_txn_id = $updateTxn;
                $asset->save();
            }

            $newAsset = [   //local db
                'name' => $asset->name,
                'description' => $asset->description,
                'category_id' => $asset->category_id,  // 'type_id' => $request->type_id //Frontend devs need to change type_id to category_id
                'assetid' => $asset->assetid,
                'skydahid' => $this->generate_random_string(),            
                'user_id' => auth()->user()->id,
                'transferable' => $asset->transferable   //$request->user_id,
            ];
            $newData = Asset::create($newAsset); //try ... catch
        
            //create a new entry on blockchain    
            $newEntry = [       //blockchain   
                'asset_id' => $newData->id, //primary key from pgsql/mysql database
                'asset_skydah_id' => $asset->skydahid,   //'sky-cungnuire8u8fcv8dhvd', //Asset skydah ID (12-16 char alphanumeric string)
                'asset_type' => $asset->name, //Asset type , can be assigned from a list of constants plus asset model
                'asset_type_id' => $asset->category_id, //Type of ID associated with asset - IMEI, serial,...
                'asset_hash' => $asset->assetid, //message digest for this asset. passing a message digest is also recommended
                'asset_skydah_owner' => $asset->user_id,// Current owner of asset
                'asset_transferable' => $asset->transferable
            ];
            $insertTxn = $this->createAsset($newEntry); //save on blockchain //try ... catch

            if($insertTxn){
                $newData->transactionId = $insertTxn;
                $newData->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Asset details have been updated.'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset could not be updated on the blockchain!'
                ], 500);
            }
            /*
            //This try --- catch is not required here. Remember to delete
    try {
        $user = User::findOrFail($request->input('user_id'));
    } catch (ModelNotFoundException $exception) {
        return back()->withError($exception->getMessage())->withInput();
    }

            return response()->json([
                'success' => true,
                'message' => 'Asset details have been updated.'
            ], 200);    */
        } else
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be updated!'
            ], 500);
    }

    public function destroy(Request $request)
    {
    //$user = $this->getTestUser();
        $id = $request->id;
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be found!'
            ], 400);
        }
 
        if ($asset->delete()) {

            //invalidate on blockchain
            $txnID = $this->setValidity($id, false);

            //update txnID on our database
            $asset->deletion_txn_id = $txnID;
            $asset->save();

            return response()->json([
                'success' => true,
                'message' => 'Asset was successfully deleted!'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be deleted!'
            ], 500);
        }
    }



//Kenny 
    public function add_asset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assetid' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',

        ]);

        /*
        public function uploadFile() {
    // check if upload is valid file (example: request()->file('myFileName')->isValid() )

    // calculate hash of a file
    $fileHash = sha1_file( request()->file('myFileName')->path() );

    //search for file in database and block same file upload (Files is Elequent model)
    if( Files::where('hash', $fileHash)->firstOrNull() != null) {
        abort(400); // abort upload
    }

    // do something with file and save hash to DB
}
        */
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $asset = Asset::where('assetid', $request->assetid)->first(); //could further filter with asset_type as additional where clause
        if ($asset) {
            $title = 'Skydah Alert: Possible Recovery';
            $alert = "It appears someone is trying to register your asset ( ". $asset->name ." ) on Skydah. If you lost this asset, kindly request more info from your dashboard";
            $recipients = $asset->user->phone;
            
//            $this->getTestUser();
            $secondary_owner = auth()->user()->id;

            //log this in the DB so the original device owner can view it on their dashboard...
            //It'll probably be best to simply log it in the db and only alert users and agencies if/when the asset is flagged as missing
            $recoveryData = [
                'asset_id' => $asset->id,
                'secondary_owner' => $secondary_owner,    //auth()->user()->id,
                'location' => '22 Otigba Stree, Computer Village, Ikeja, Lagos - Nigeria', //Get these from frontend
                'lat' => '2.789005',
                'lng' => '0.675589'
            ];
            $recovery = Recovery::create($recoveryData);

            $this->sendSMS($recipients, $alert);
            $this->sendEmail($asset->user->email, $title, $alert);

            return response()->json([
                'success' => false, 
                'message' => 'An asset with this ID already exists! If you believe this is an error, Contact Us so we would help resolve the issue.'
            ], 422);
        }

    //    $user = $this->getTestUser();
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,  // 'type_id' => $request->type_id //Frontend devs need to change type_id to category_id
            'assetid' => $request->assetid,
            'skydahid' => $this->generate_random_string(),            
            'user_id' => auth()->user()->id,
            'transferable' => $request->transferable   //$request->user_id,
        ];
//dd($data);
        $asset = Asset::create($data);
        
        if($asset) 
        {
            $message = "Asset successfully created!"; $code = 200;

            //Push to block chain
            $bcData = [         
                'asset_id' => $asset->id, //primary key from pgsql/mysql database
                'asset_skydah_id' => $data['skydahid'],   //'sky-cungnuire8u8fcv8dhvd', //Asset skydah ID (12-16 char alphanumeric string)
                'asset_type' => $data['name'], //Asset type , can be assigned from a list of constants plus asset model
                'asset_type_id' => $data['category_id'], //Type of ID associated with asset - IMEI, serial,...
                'asset_hash' => $data['assetid'], //message digest for this asset. passing a message digest is also recommended
                'asset_skydah_owner' => $data['user_id'],// Current owner of asset
                'asset_transferable' => $data['transferable']
            ];
            $bcResult = $this->createAsset($bcData); //save on the blockchain and return a txnID
            if ($bcResult) $message = "Asset added to blockchain"; else  $message = "Blockchain failed!";

            $newAsset = Asset::find($asset->id);
            if ($newAsset) {
                $newAsset->transactionId = $bcResult; 
                $newAsset->save();//add the blockchain txnID to the newly created asset
                $message = "Transaction ID successfully saved!";
                $code = 200;
            } else {
                $message = "Asset was registered successfully on Skydah & on the BlockChain, but the transaction ID could not be saved.";
                $code = 500;
                return response()->json($message, $code);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Congrats! Your asset is now protected by Skydah.',
                'data' => $asset->toArray()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be added! Please, try again.'
            ], 500);
        }
 
    }

    public function generate_company_codes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number_of_codes' => 'required',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }
        
        $number_of_codes = $request->number_of_codes;
        $user_id = $request->user_id;

        $codes = array();

        for($i=1; $i<=$number_of_codes; $i++) {
            $data = [
                'user_id' => $request->user_id,
                'code' => $this->generate_random_string()
            ];

            CompanyCode::create($data);
            array_push($codes, $data);
        }

        return $this->sendSuccess('Codes successfully generated', $codes);
    }

    public function get_company_codes($user_id)
    {
        $company_codes = CompanyCode::where('user_id', $user_id)->get();

        if($company_codes != null or $company_codes != NULL) {
            return $this->sendSuccess('Company code successfully retrieved', $company_codes);
        } else {
            return $this->sendError('No compamy code found', $company_codes = []);
        }
    }

    public function sendEmail($email, $title = null, $body)
    {
        $details = [
            'title' => $title,
            'body' => $body
        ];
       
        Mail::to($email)->send(new MyTestMail($details));
       
    //    dd('Email is Sent, please check your inbox.');
    }

    public function generate_random_string($length = 15) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function upload_bulk_assets(Request $request)
    {
        $file = $request->file('file');

        // File Details 
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $tempPath = $file->getRealPath();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
  
        // Valid File Extensions
        $valid_extension = array("csv");
  
        // 2MB in Bytes
        $maxFileSize = 2097152; 
  
        // Check file extension
        if(in_array(strtolower($extension),$valid_extension)){
  
          // Check file size
          if($fileSize <= $maxFileSize){
  
            // File upload location
            $location = 'uploads';
  
            // Upload file
            $file->move($location,$filename);
  
            // Import CSV to Database
            $filepath = public_path($location."/".$filename);
  
            // Reading file
            $file = fopen($filepath,"r");
  
            $importData_arr = array();
            $i = 0;
  
            while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
               $num = count($filedata );
               
               // Skip first row (Remove below comment if you want to skip the first row)
               if($i == 0){
                  $i++;
                  continue; 
               }
               for ($c=0; $c < $num; $c++) {
                  $importData_arr[$i][] = $filedata [$c];
               }
               $i++;
            }
            fclose($file);
  
            $assets = [];

            // Insert to MySQL database
            foreach($importData_arr as $importData){

                $data = [
                    'name' => $importData[0],
                    'description' => $importData[1],
                    'skydahid' => $this->generate_random_string(),
                ];

                array_push($assets, $data);

                Asset::create($data);
            }
            return $this->sendSuccess('Asset successfully created', $assets);
          }else{
            return $this->sendError('File too large. File must be less than 2MB.', $assets = []);
          }
  
        }else{
           return $this->sendError('Invalid File Extension', $assets = []);
        }
  
    }
    
    public function transfer(Request $request)
    {
        //Upon successful transfer, apply softdelete on the previous owner in blockchain
        $asset = auth()->user()->assets->find($request->id);

        if( !($asset) ){
            return response()->json(['message' => 'Asset not found!'], 404);
        }

        $transferData = [
            'user_id' => auth()->user()->id,
            'newOwner' => $request->transferTo,
            'asset_id' => $request->id,
            'transferReason' => $request->transferReason
        ];

        $transfer = Transfer::create($transferData);

        if ( ! ($transfer) ) return response()->json(['message' => 'Transfer failed! Please try again.'], 422);
        
        //Change asset's user_id to the new owner's
        $asset->user_id = $request->transferTo;
        $asset->save();
        $message = 'Asset has been fully transferred! Forwarding to the blockchain...';
        
        //effect transfer on the blockchain        
        $txnID = $this->transferAsset($request->id, $request->transferTo);

        //add transaction ID to the transferred record
        $transfer->transactionId = $txnID;
        $transfer->save();

        $newOwner = User::find($request->transferTo);

        return response()->json(['message' => 'You have successfully transferred '.$asset->name.' to '.$newOwner->name], 200);
    }

    public function flagAssetAsMissing(Request $request)
    {
        //Display details of "recoveries" on reporting user's dashboard

    }

}

        //Use these where ONLY authenticated users are allowed to view asset
        //$user = $this->getTestUser();
        //$asset = auth()->user()->assets()->where('skydahid', $ref)->orWhere('assetid', $ref)->first();
        //