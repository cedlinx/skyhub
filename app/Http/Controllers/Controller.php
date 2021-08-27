<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use AfricasTalking\SDK\AfricasTalking;

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
}
