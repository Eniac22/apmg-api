<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Officer;
use App\Models\Business;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class OfficerController extends Controller
{

    public function index(Request $request, Department $department)
    {
        // Get the ID of the authenticated user's business (assuming you're using authentication)
        $adminId = Auth::id();
        $businessId = Business::where('admin_id', $adminId)->value('id');

        // Check if the specified department belongs to the authenticated user's business
        if ($department->business_id !== $businessId) {
            return response()->json(['error' => 'Unauthorized access to department'], 403);
        }

        // Retrieve all officers associated with the specified department
        $officers = Officer::where('department_id', $department->id)->get();

        return response()->json($officers);
    }
    
    public function create(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'contact_number' => 'nullable|string',
            // Add more validation rules as needed
        ]);

        // Create a new user for the officer
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'officer', // Assuming 'officer' is the role for officers
        ]);

        // Create a new officer associated with the user and department
        $officer = Officer::create([
            'user_id' => $user->id,
            'contact_number' => $request->contact_number
        ]);

        // Associate the officer with the department
        $department->officers()->attach($officer->id);

        return response()->json($officer, 200);
    }

    public function update(Request $request, Department $department, Officer $officer)
    {
        $adminId = Auth::id();
        $businessId = Business::where('admin_id', $adminId)->value('id');
        // Check if the department belongs to the specified business
        if ($department->business_id !== $businessId) {
            return response()->json(['error' => 'The department does not belong to the specified business.'], 403);
        }

        // Check if the officer belongs to the specified department
        if ($officer->department_id !== $department->id) {
            return response()->json(['error' => 'The officer does not belong to the specified department.'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $officer->user_id,
            'contact_number' => 'nullable|string',
            // Add more validation rules as needed
        ]);

        // Update the user associated with the officer
        $officer->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Update the officer information
        $officer->update([
            'contact_number' => $request->contact_number,
        ]);

        return response()->json($officer, 200);
    }

    // Delete an existing officer
    public function destroy(Request $request, $departmentId, $officerId)
    {
        $officer = Officer::findOrFail($officerId);

        // Check if the officer belongs to the specified department
        if (!$officer->departments()->where('department_id', $departmentId)->exists()) {
            return response()->json(['error' => 'The officer does not belong to the specified department.'], 403);
        }

        // Detach the officer from the department
        $officer->departments()->detach($departmentId);
        // Delete the officer
        $officer->delete();
        
        return response()->json("success", 200);
    }


}
