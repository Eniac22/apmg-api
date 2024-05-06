<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leaves;
use App\Models\Business;
use App\Models\Officer;
use App\Models\Department;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function getOfficerLeaves()
    {
        $userID = Auth::id();
        $officer = Officer::where('user_id', $userID)->with('user', 'departments')->firstOrFail();
        $officerId = $officer->id;
        $leaves = Leaves::where('officer_id', $officerId)->with(['department.business'])->paginate(env('PER_PAGE', 10));
        return response()->json($leaves);
    }

    public function addOfficerLeave(Request $request)
    {
        $validatedData = $request->validate([
            'officer_id' => 'nullable',
            'department_id' => 'required',
            'start_date'    => 'required',
            'end_date'    => 'required',
        ]);
        $userId = Auth::id();
        
        $officer = Officer::where('user_id', $userId)->with('user', 'departments')->firstOrFail();
        $officerId = $officer->id;
        $validatedData['officer_id'] = $officerId;

        Leaves::create($validatedData);

        return response()->json(['message' => 'Leave created successfully'], 201);

    }

    public function deleteLeave($id)
    {
        $leave = Leaves::findOrFail($id);
        if($leave) {
            $leave->delete();
        }
        return response()->json("success", 200);
    }

    public function addBusinessLeave(Request $request) {
        $userID = Auth::id();
        $business = Business::where('admin_id', $userID)->first();

        if($business) {
            Leaves::create([
                'business_id' => $business->id,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ]);
        }
        return response()->json(['message' => 'Leave created successfully'], 201);
    }

    public function getBusinessLeaves()
    {
        $userID = Auth::id();

        $business = Business::where('admin_id', $userID)->first();
        $leaves = Leaves::where('officer_id', null)
                        ->where('business_id', $business->id)
                        ->with(['department.business'])
                        ->paginate(env('PER_PAGE', 10));

        return response()->json($leaves);
    }

    public function getBusinessOfficerLeaves()
    {
        $userID = Auth::id();
        $business = Business::where('admin_id', $userID)->first();
        $leaves = collect();

        foreach ($business->departments as $department) {
            foreach ($department->officers as $officer) {
                $leave = Leaves::where('officer_id', $officer->id)
                                ->where('department_id', $department->id)
                                ->paginate(env('PER_PAGE', 10));

                $leave->getCollection()->transform(function ($item) use ($officer, $department) {
                    $item->officer = $officer;
                    $item->department = $department;
                    $item->user = $officer->user;
                    return $item;
                });
                    
                if($leave->items()) {
                    $leaves = $leaves->merge($leave);
                }
            }
        }
        return response()->json($leaves);
    }

    // for user get business + officer leaves
    public function getBusinessOfficerLeavesForUser($business_id) 
    {
        $business = Business::with(['departments.officers.leaves', 'leaves'])->find($business_id);

        // Access the leaves through the relationships
        $businessLeaves = $business->leaves;

        $departmentOfficerLeaves = $business->departments->flatMap(function ($department) {
            return $department->officers->flatMap(function ($officer) {
                return $officer->leaves;
            });
        });

        $allLeaves = $businessLeaves->merge($departmentOfficerLeaves);
        $processedDates = $this->processDates($allLeaves);

        return response()->json($processedDates);
    }

    // process leave dates
    private function processDates($leaves) 
    {
        $allDates = [];

        $leaves->each(function ($leave) use (&$allDates) {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);

            // Generate date range for each leave
            $dateRange = $startDate->toPeriod($endDate)->toArray();

            // Format dates without time component and in UTC
            $formattedDates = array_map(function ($date) {
                return $date->toDateString(); // Convert to YYYY-MM-DD format
            }, $dateRange);

            // Merge formatted date range with allDates array
            $allDates = array_merge($allDates, $formattedDates);
        });

        // Sort and unique the dates array
        $allDates = array_values(array_unique($allDates));

        return $allDates;
    }

}
