<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index()
    {
        $userID = Auth::id();
        $result = [];
        $appointments = Appointment::with(['user', 'department', 'officer'])
        ->where('user_id', $userID)
        ->orderBy('department_id')
        ->orderBy('officer_id')
        ->get();
    
    // Now you can access the current token for each officer within appointments
        $i = 0;
        foreach ($appointments as $appointment) {
            $currentToken = $appointment->officer->current_token;
            $appointment->currentToken = $currentToken; // Assigning to a new key
            $result[$i] = $appointment;
            $i++;
        }
    
        
        return response()->json($result);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'officer_id' => 'required',
            'slot_datetime' => 'required',
            'department_id' => 'required',
            'business_id' => 'required',
        ]);

        $officerId = $validatedData['officer_id'];
        $departmentId = $validatedData['department_id'];
        $slotDatetime = Carbon::parse($validatedData['slot_datetime'])->startOfDay();
        
        // Check if it's the first appointment of the day for the officer in the department
        $officerDepartment = DB::table('officers_to_department')
            ->where('officer_id', $officerId)
            ->where('department_id', $departmentId)
            ->first();

        $currentDate = Carbon::now()->toDateString(); // Get the current date in "Y-m-d" format
        
        if (Carbon::parse($officerDepartment->last_token_updated_at)->toDateString() == $currentDate) {
            // It's the first appointment of the day for the officer in the department
            $slot = $officerDepartment->last_token + 1;
        } else {
            // Increment last_token for subsequent appointments
            $slot = 100;
        }
        // Create the appointment
        $validatedData['slot_id'] = $slot;
        $validatedData['last_token'] = $slot;
        $appointment = Appointment::create($validatedData);
        
        // Update the last_token for the officer in the department
        if ($officerDepartment) {
            DB::table('officers_to_department')
                ->where('id', $officerDepartment->id)
                ->update([
                    'last_token' => $slot,
                    'last_token_updated_at' => Carbon::now(),
                ]);
        }

        return response()->json($appointment, 201);
    }
}
