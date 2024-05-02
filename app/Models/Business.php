<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = ['user_id', 'business_name', 'address', 'contact_number'];

    // Define the relationship between Business and User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departments() {
        return $this->hasMany(Department::class);
    }
}
