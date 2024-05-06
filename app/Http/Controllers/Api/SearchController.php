<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Department;
use App\Models\Officer;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        if(empty($query)){
            return response()->json([]);
        }
        $businesses = Business::where('name', 'like', "%$query%")
            ->orWhere('address', 'like', "%$query%")
            ->orWhere('contact_number', 'like', "%$query%")
            ->get();

        $departments = Department::where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('name', 'like', "%$query%")
                ->whereHas('officers');
            })->orWhere('contact_number', 'like', "%$query%")->get();
        
        $officers = DB::table('officers_to_department')
            ->join('officers', 'officers_to_department.officer_id', '=', 'officers.id')
            ->join('departments', 'officers_to_department.department_id', '=', 'departments.id')
            ->join('users', 'officers.user_id', '=', 'users.id')
            ->where(function ($queryBuilder) use ($query) {
                $queryBuilder->where('officers.contact_number', 'like', "%$query%")
                    ->orWhere('users.name', 'like', "%$query%")
                    ->orWhere('users.email', 'like', "%$query%");
            })
            ->select(
                'officers.id as officer_id',
                'officers.contact_number as contact_number',
                'departments.id as department_id',
                'departments.name as department_name',
                'users.id as user_id',
                'users.name as user_name'
            )
            ->get();
        
        $results = [];

        foreach ($businesses as $business) {
            $results[] = [
                'type' => 'business',
                'id' => $business->id,
                'name' => $business->name,
                'address' => $business->address
            ];
        }

        foreach ($departments as $department) {
            $results[] = [
                'type' => 'department',
                'id' => $department->id,
                'name' => $department->name,
                'contact_number' => $department->contact_number,
            ];
        }

        foreach ($officers as $officer) {
            $results[] = [
                'type' => 'officer',
                'id' => $officer->officer_id,
                'contact_number' => $officer->contact_number,
                'department_id' => $officer->department_id,
                'department_name' => $officer->department_name,
                'name' => $officer->user_name,
            ];
        }

        return response()->json($results);
    }

    public function getSelected($id, $type)
    {
        $business = [];
        $departments = [];
        $officers = [];

        switch ($type) {
            case 'business':
                $business = Business::where('id', $id)->get()->toArray();
                $departments = Department::where('business_id', $id)->whereHas('officers')->get()->toArray();
                break;

            case 'department':
                $departments = Department::where('id', $id)->get()->toArray();
                $business = Business::where('id', $departments[0]['business_id'])->get()->toArray();
                $officerToDepartment = DB::table('officers_to_department')->where('department_id', $id)->get();
                foreach ($officerToDepartment as $key => $value) {
                    $officers = Officer::where('id', $value->officer_id)->with('user')->get();
                }
                break;

            case 'officer':
                $officers = Officer::where('id', $id)->with('user')->get()->toArray();
                $departmentToOfficer = DB::table('officers_to_department')->where('officer_id', $id)->first();
                $departments = Department::where('id', $departmentToOfficer->department_id)->get()->toArray();
                $business = Business::where('id', $departments[0]['business_id'])->get()->toArray();
                break;

            default:
                break;
        }

        return response()->json([
            'businesses' => $business,
            'departments' => $departments,
            'officers' => $officers,
        ]);
    }

    public function businessDepartmentSearch(Request $request) {
        $query = $request->input('q');
        if(empty($query)){
            return response()->json([]);
        }
        $userId = Auth::id();
        $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();
        
        $businesses = collect();

        $filteredDepartments = $officer->departments()->where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('name', 'like', "%$query%");
        })->get();


        foreach ($officer->departments as $department) {
            if ($department->business) {
                $businesses = $businesses->merge(Business::where('name', 'like', "%$query%")
                ->where('id', $department->business->id)
                ->get());
            }
        }
    
        $uniqueBusinesses = $businesses->unique('id')->values();

        $results = [];

        foreach ($uniqueBusinesses as $business) {
            $results[] = [
                'type' => 'business',
                'id' => $business->id,
                'name' => $business->name,
                'address' => $business->address
            ];
        }

        foreach ($filteredDepartments as $department) {
            $results[] = [
                'type' => 'department',
                'business' => $department->business,
                'id' => $department->id,
                'name' => $department->name,
                'contact_number' => $department->contact_number,
            ];
        }
    
        return response()->json($results);
    }   
}
