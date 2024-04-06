<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'user_id',
        'officer_id',
        'slot_datetime',
        'department_id',
        'business_id',
    ];

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship with the Officer model
    public function officer()
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }

    // Define the relationship with the Department model
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Define the relationship with the Business model
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
