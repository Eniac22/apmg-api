<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function showAllSuperDepartments()
    {
        // Get the ID of the authenticated user (assuming you're using authentication)
        $adminId = Auth::id();

        // Retrieve the business ID associated with the admin user
        $businessId = Business::where('admin_id', $adminId)->value('id');

        // Retrieve all departments with super_department_id as null under the business with the retrieved business ID
        $superDepartments = Department::where('business_id', $businessId)
            ->whereNull('super_department_id')
            ->get();

        return response()->json($superDepartments);
    }
    
    public function listSubDepartments(Request $request, Department $department)
    {
        // Get the ID of the authenticated user's business (assuming you're using authentication)
        $adminId = Auth::id();
        $businessId = Business::where('admin_id', $adminId)->value('id');

        // Check if the specified department belongs to the authenticated user's business
        if ($department->business_id !== $businessId) {
            return response()->json(['error' => 'Unauthorized access to department'], 403);
        }

        // Retrieve all sub-departments where the super_department_id matches the ID of the specified department
        $subDepartments = Department::where('super_department_id', $department->id)->get();

        return response()->json($subDepartments);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'contact_number' => 'required|string',
            'average_processing_time' => 'nullable|integer',
            // Add more validation rules as needed
        ]);

        // Get the ID of the authenticated user (assuming you're using authentication)
        $adminId = Auth::id();

        // Retrieve the business ID associated with the admin user
        $businessId = Business::where('admin_id', $adminId)->value('id');

        // Create a new department with the validated data
        $department = Department::create([
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'average_processing_time' => $request->average_processing_time,
            'business_id' => $businessId,
            // Add more fields as needed
        ]);

        return response()->json($department, 201);
    }
}
