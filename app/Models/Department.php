<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'contact_number', 'business_id', 'average_processing_time', 'super_department_id'];

    // Define the relationship with the Business model
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Define the relationship with the Super Department model (self-referencing)
    public function superDepartment()
    {
        return $this->belongsTo(Department::class, 'super_department_id');
    }

    // Define the relationship with Sub Departments (children departments)
    public function subDepartments()
    {
        return $this->hasMany(Department::class, 'super_department_id');
    }

    public function officers()
    {
        return $this->belongsToMany(Officer::class, 'officers_to_department', 'department_id', 'officer_id');
    }
}
