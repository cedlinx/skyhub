<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Company;
use App\Http\Traits\GetInitials;

class CompanyController extends Controller
{
    use GetInitials;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::get(); 
        return response()->json([
            $companies->toArray()
        ], 200);
    }


    public function add_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies',
            'email' => 'nullable|email',    //use the same email used for creating the user account... it'll become the super user
            'group_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;
        if ( is_null($email) ) $email = auth()->user()->email;

        $data = [
            'name' => $request->name,
            'email' => $email,
            'group_id' => $request->group_id,
            'code' => ''
        ];

        $company = Company::create($data);
        $company->code = $this->generate($company->name) . $company->id;
        $company->save();
        
        if($company != null) {
            $user = User::where('email', $company->email)->update(['group_id' => $company->group_id, 'role_id' => 1]); //update the user registering this company to super user and agency/enterprise group
            if (! $user) return response()->json(['message' => 'It appears you used different emails for your user and company accounts. It is recommended that you use the email address for a smooth access control experience.'], 201);
            return $this->sendSuccess('Company successfully created. Let your Team members provide this company code: '.$company->code. ', during registration', $company);
        } else {
            return $this->sendError('Unable to create company. Please try again', $company = []);
        }
    }

    public function edit_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies',
            'company_id' => 'required',
            'group_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $name = $request->name;
        $company_id = $request->company_id;
        $group = $group_id;

        $company = Company::where('id', $company_id)->exists();

        if($company) {
            Company::where('id', $company_id)->update(['name' => $name, 'group_id' => $group]);
            return $this->sendSuccess('Company successfully updated', $name);
        } else {
            return $this->sendError('Company ID '.$company_id.' does not exist', $company = []);
        }
    }
    
     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $company = Company::find($request->id);
        if ( ! ($company) ) return response()->json(['Sorry! Company not found.'], 422);
        
        $coy = $company->name;
        $company->delete();
        return response()->json([
            'success' => true,
            'message' => $coy. ' has been deleted!'
        ],200);
    }

}
