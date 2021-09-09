<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function save_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_reference' => 'required',
            'amount_paid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        } else {
            $data = [
                'user_id' => $request->user_id,
                'payment_reference' => $request->payment_reference,
                'payment_type' => $request->payment_type,
                'amount_paid' => $request->amount_paid,
                'payment_status' => $request->payment_status,
            ];

            $payment = Payment::create($data);

            if($payment != null) {
                return $this->sendSuccess('Payment successfully created', $payment);
            } else {
                return $this->sendError('Unable to create Payment. Please try again', $payment = []);
            }
        }
    }

}
