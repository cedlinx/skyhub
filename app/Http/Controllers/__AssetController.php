<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Asset;
use App\Models\CompanyCode;
use Mail;
use App\Mail\MyTestMail;
//use App\Http\Controllers\SmsServiceController;

use App\Models\User;
use App\Models\Recovery;
                                                        //NOTE: Change type_id in DB to category_id via migration

class AssetController extends Controller
{

    public function getTestUser()
    {
        $user = User::where('email', 'cedlinx@yahoo.com')->first();
        auth()->login($user);
        return $user;
    }

    public function index()
    {
        //Retrieve and display all assets belonging to the logged in user
        $assets = auth()->user()->assets;   //->asset;
 
        return response()->json([
            'success' => true,
            'data' => $assets
        ], 200);
    }

    public function show($ref)
    {   
        //Query Skydah for a specific asset using either the assetid or the skydahid
        $asset = Asset::where('skydahid', $ref)->orWhere('assetid', $ref)->first();
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found! Please secure your asset by registering it on Skydah.'
            ], 400);
        }
 
        return response()->json([
            'success' => true,
            'data' => $asset->toArray()
        ], 200);
    }

    public function store(Request $request)
    {
        //MERGE this with add_asset below from Kenny
        //Then inplement the blockchain
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assetid' => 'nullable|string|max:255',
            'user_id' => 'required|integer',
            'category_id' => 'nullable|integer',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }
 
        $asset = new Asset();
        $asset->name = $request->name;
        $asset->description = $request->description;
        $asset->skydahid = $request->xyz;   /*GET THIS FROM CODE GENERATION*/ ;
        $asset->assetid = $request->assetid;
        $asset->user_id = $request->user_id;
        $asset->category_id = $request->category_id;
 
        if (auth()->user()->assets()->save($asset))
            return response()->json([
                'success' => true,
                'message' => 'Congrats! Your asset is now protected by Skydah.',
                'data' => $asset->toArray()
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be added! Please, try again.'
            ], 500);
    }

    public function update(Request $request, $id)
    {
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be found!'
            ], 400);
        }
 
        $updated = $asset->fill($request->all())->save();
 
        if ($updated)
            return response()->json([
                'success' => true
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be updated!'
            ], 500);
    }

    public function destroy($id)
    {
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be found!'
            ], 400);
        }
 
        if ($asset->delete()) {
            return response()->json([
                'success' => true
            ], 204);
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
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $asset = Asset::where('assetid', $request->assetid)->first(); //could further filter with asset_type as additional where clause
        if ($asset) {
            $title = 'Skydah Alert: Possible Recovery';
            $alert = "It appears someone is trying to register your asset ( ". $asset->name ." ) on Skydah. If you lost this asset, kindly request more info from your dashboard";
            $recipients = $asset->user->phone;
            
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

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,  // 'type_id' => $request->type_id //Frontend devs need to change type_id to category_id
            'assetid' => $request->assetid,
            'skydahid' => $this->generate_random_string(),            
            'user_id' => auth()->user()->id   //$request->user_id,
        ];

        $asset = Asset::create($data);
        
        if($asset) 
            return response()->json([
                'success' => true,
                'message' => 'Congrats! Your asset is now protected by Skydah.',
                'data' => $asset->toArray()
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be added! Please, try again.'
            ], 500);
 
    }

    public function generate_company_codes(Request $request)
    {
        $number_of_codes = $request->number_of_codes;
        $company_id = $request->company_id;

        $codes = array();

        for($i=1; $i<=$number_of_codes; $i++) {
            $data = [
                'company_id' => $request->company_id,
                'code' => $this->generate_random_string()
            ];

            CompanyCode::create($data);
            array_push($codes, $data);
        }

        return $this->sendSuccess('Codes successfully generated', $codes);
    }

    public function get_company_codes($company_id)
    {
        $company_codes = CompanyCode::where('company_id', $company_id)->get();

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
    
}

        //Use these where ONLY authenticated users are allowed to view asset
        //$user = $this->getTestUser();
        //$asset = auth()->user()->assets()->where('skydahid', $ref)->orWhere('assetid', $ref)->first();
        //