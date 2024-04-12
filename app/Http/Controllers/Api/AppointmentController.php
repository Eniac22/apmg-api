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
        foreach ($appointments as $appointment) {
            $currentToken = $appointment->officer->current_token;
            $appointment->currentToken = $currentToken; // Assigning to a new key

            // Include officer's contact number in the response
            $appointment->officer_contact_number = $appointment->officer->contact_number;

            // Include officer's name if available
            if ($appointment->officer->user) {
                $appointment->officer_name = $appointment->officer->user->name;
            }

            $result[] = $appointment;
        }

        return response()->json($result);
    }

    public function viewAppoinment($id) {
        $appointment = Appointment::findOrFail($id);
        return response()->json($appointment);
    }

    public function deleteAppointment($id) {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        
        return response()->json(['message' => 'Appointment deleted successfully']);
    }

    public function updateAppointment(Request $request, $id) {
        $appointment = Appointment::findOrFail($id);
        
        $validatedData = $request->validate([
            // Define your validation rules here based on your needs
            // Example:
            'slot_id' => 'required|exists:slots,id',
            'user_id' => 'required|exists:users,id',
            'officer_id' => 'required|exists:officers,id',
            'department_id' => 'required|exists:departments,id',
            'business_id' => 'required|exists:businesses,id',
            'slot_datetime' => 'required|date',
            'reason' => 'required|string',
        ]);
        
        $appointment->update($validatedData);
        
        return response()->json(['message' => 'Appointment updated successfully']);
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
