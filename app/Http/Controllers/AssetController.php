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
            'skydahid' => $this->generate_random_string()
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
    
}
