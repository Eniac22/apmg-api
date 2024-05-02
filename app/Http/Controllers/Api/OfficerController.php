<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


use App\Models\User;
use App\Models\Officer;
use App\Models\Business;
use App\Models\Department;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficerController extends Controller
{
    public function getAllOfficers()
    {
        $officers = Officer::all()->pluck('name', 'id');
        return response()->json($officers);
    }

    public function getAllDepartments()
    {
        // Get the logged-in officer
        $officer = Auth::id();

        // If officer not found, return empty array
        if (!$officer) {
            return response()->json([]);
        }

        // Retrieve all departments assigned to the officer
        $departments = $officer->departments()->with('subDepartments')->get();

        return response()->json($departments);
    }

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
        $officers = $department->officers()->with('user')->get();

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

    public function showAssignedDepartments($userId=null) {
        // Retrieve the officer
        if(!$userId){
            $userId = Auth::id();
        }
        $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();

        // Access additional details
        $userDetails = $officer->user->toArray();
        $departmentDetails = $officer->departments->toArray();

        // Merge user details and department details into a single array
        $officerDetails = array_merge($userDetails, ['departments' => $departmentDetails]);
        return response()->json($officerDetails);
    }

    public function updateToken(Request $request, $departmentId)
    {
        $userId = Auth::id();
        
        $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();

        // Retrieve officer ID
        $officerId = $officer->id;

        // Retrieve department for the officer
        $department = Department::findOrFail($departmentId);
        $officerDepartment = $department->officers()->where('officer_id', $officerId)->first();
        if (!$officerDepartment) {
            return response()->json(['message' => 'Officer not assigned to the specified department.']);
        }

        $lastUpdated = Carbon::parse($officerDepartment->pivot->current_token_updated_at)->setTimezone(config('app.timezone'));
         // Increment, decrement, or edit the current_token field based on request
        if (Carbon::parse($officerDepartment->pivot->current_token_updated_at)->isToday()) {
            // Increment, decrement, or edit the current_token field based on request
            $currentToken = $officer->current_token;

            if ($request->has('increment')) {
                $newToken = $currentToken + 1;
            } elseif ($request->has('decrement')) {
                $newToken = $currentToken - 1;
            } elseif ($request->has('edit_token')) {
                $newToken = $request->input('edit_token');
            }

            // Update the current token
            $department->officers()->updateExistingPivot($officerId, ['current_token' => $newToken]);
            return response()->json(['message' => 'Token updated succeddssfully']);
        } else {
            $newToken = 100;
            $department->officers()->updateExistingPivot($officerId, ['current_token' => $newToken]);
            return response()->json(['message' => 'Token updated successfully']);
        }

        return response()->json(['message' => 'Token updated sucssscessfully']);
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

    public function getOfficers($depId) 
    {
        $officers = [];
        $officerToDepartment = DB::table('officers_to_department')->where('department_id', $depId)->get();
        foreach ($officerToDepartment as $key => $value) {
            $officer = Officer::where('id', $value->id)->with('user')->get();
            if($officer) {
                $officers[] = $officer->toArray();
            }
        }
        $flattenedOfficers = array_merge(...$officers);
        return response()->json($flattenedOfficers);
    }

    public function getAllAppointments($departmentId, Request $request) 
{
    $userId = Auth::id();
    $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();
    $department = Department::findOrFail($departmentId);
    $officerDepartment = $department->officers()->where('officer_id', $officer->id)->first();

    $appointments = $officer->appointments()->where('department_id', $departmentId)->with('user', 'department');

    // Retrieve search query and date range from the request
    $searchQuery = $request->input('search_query');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // Apply global search filter
    if ($searchQuery) {
        $appointments->where(function ($query) use ($searchQuery) {
            $query->whereHas('user', function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            })
            ->orWhereHas('department', function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            })
            ->orWhereHas('officer.user', function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            })
            ->orWhere('slot_id', 'like', '%' . $searchQuery . '%')
            ->orWhere('reason', 'like', '%' . $searchQuery . '%');
        });
    }

    // Apply date range filter
    if ($startDate && $endDate) {
        $appointments->whereBetween('slot_datetime', [$startDate, $endDate]);
    }

    // Apply slot_id filter
    $appointments->where('slot_id', '>=', $officerDepartment->current_token);

    // Fetch the filtered appointments
    // Paginate the results
    $appointments->orderBy('slot_id', 'asc');
    $perPage = env('PER_PAGE', 10);
    $appointments = $appointments->paginate($perPage);
    // $appointments = $appointments->get();

    return response()->json($appointments);
}

    public function getSpecificDepartment ($id) {
        $userId = Auth::id();
        $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();
        $officerId = $officer->id;

        // Get the specific department for the officer
        $department = $officer->departments()->where('departments.id', $id)->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found for this officer.'], 404);
        }

        // Check if the current_token is null or not set
        if (!isset($department->pivot->current_token) || is_null($department->pivot->current_token)) {
            // Set current_token to 100 if it's null
            $department->pivot->current_token = 100;

            // Update the value in the pivot table
            $officer->departments()->updateExistingPivot($id, ['current_token' => 100]);
        }

        // Include current_token in the response
        $response = $department->toArray();
        $response['current_token'] = $department->pivot->current_token;

        return response()->json($response);

    }

    public function officerAppointments($depId, $officerId) {
        $appointments = Appointment::where('officer_id', $officerId)->where('department_id', $depId)->with('user')->paginate(env('PER_PAGE', 10));
        return response()->json($appointments);
    }

}
