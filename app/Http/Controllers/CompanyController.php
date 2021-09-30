<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Company;

class CompanyController extends Controller
{
    public function add_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'name' => $request->name,
        ];

        $company = Company::create($data);
        
        if($company != null) {
            return $this->sendSuccess('Company successfully created', $company);
        } else {
            return $this->sendError('Unable to create company. Please try again', $company = []);
        }
    }

    public function edit_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies',
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $name = $request->name;
        $company_id = $request->company_id;

        $company = Company::where('id', $company_id)->exists();

        if($company) {
            Company::where('id', $company_id)->update(['name' => $name]);
            return $this->sendSuccess('Company successfully updated', $company_id);
        } else {
            return $this->sendError('Company ID '.$company_id.' does not exist', $company = []);
        }
    }
    

}
