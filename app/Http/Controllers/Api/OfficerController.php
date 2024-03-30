<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Officer;
use App\Models\Department;

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

        return response()->json($officer, 201);
    }

}
