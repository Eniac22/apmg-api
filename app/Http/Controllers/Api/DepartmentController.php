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
        $superDepartments = Department::select('departments.*')
            ->selectRaw('EXISTS (SELECT 1 FROM departments AS d WHERE d.super_department_id = departments.id) AS has_sub_departments')
            ->selectRaw('EXISTS (SELECT 1 FROM officers_to_department WHERE officers_to_department.department_id = departments.id) AS has_officers')
            ->where('business_id', $businessId)
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
        $subDepartments = Department::select('departments.*')
            ->selectRaw('EXISTS (SELECT 1 FROM departments AS d WHERE d.super_department_id = departments.id) AS has_sub_departments')
            ->selectRaw('EXISTS (SELECT 1 FROM officers_to_department WHERE officers_to_department.department_id = departments.id) AS has_officers')
            ->where('super_department_id', $department->id)
            ->get();

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

        // Initialize the super_department_id variable
        $superDepartmentId = null;

        // Check if the request contains a field for super_department_id and it has a value
        if ($request->has('super_department_id') && $request->filled('super_department_id')) {
            $superDepartmentId = $request->input('super_department_id');
        }

        // Create a new department with the validated data
        $department = Department::create([
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'average_processing_time' => $request->average_processing_time,
            'business_id' => $businessId,
            'super_department_id' => $superDepartmentId, // Set the super_department_id conditionally
            // Add more fields as needed
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'contact_number' => 'required|string',
            'average_processing_time' => 'nullable|integer',
            // Add more validation rules as needed
        ]);

        $department = Department::findOrFail($id);

        $department->update([
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'average_processing_time' => $request->average_processing_time,
            // Update more fields as needed
        ]);

        return response()->json($department, 200);
    }

    // Delete a department
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json('deleted', 204);
    }

}
