<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Asset;
use App\Models\CompanyCode;
use Mail;
use App\Mail\MyTestMail;

class AssetController extends Controller
{
    public function add_asset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:assets',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'skydahid' => $this->generate_random_string(),
            'user_id' => $request->user_id,
            'type_id' => $request->type_id
        ];

        $asset = Asset::create($data);
        
        if($asset) {
            // $this->sendEmail();
            // $recipients = "+2348039277193";
            // $message = "Hello world!";
            // $this->sendSMS($recipients, $message);
            return $this->sendSuccess('Asset successfully created', $asset);
        } else {
            return $this->sendError('Unable to create Asset. Please try again', $asset = []);
        }
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

    public function sendEmail()
    {
        $details = [
            'title' => 'Mail from Skydah',
            'body' => 'Test mail sent by Laravel 8 using SMTP.'
        ];
       
        Mail::to('kenny4real2001@gmail.com')->send(new MyTestMail($details));
       
        dd('Email is Sent, please check your inbox.');
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
