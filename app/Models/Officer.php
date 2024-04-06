<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Officer extends Model
{
    protected $fillable = ['user_id', 'contact_number', 'last_token', 'current_token'];

    protected $dates = [
        'last_token_updated_at',
    ];
    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'officers_to_department', 'officer_id', 'department_id');
    }
}
