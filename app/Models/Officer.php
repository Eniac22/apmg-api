<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsToMany(Department::class, 'officers_to_department')
            ->withPivot(['last_token', 'current_token', 'last_token_updated_at']);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'officer_id', 'id');
    }
    public function getCurrentTokenAttribute()
    {
        return DB::table('officers_to_department')
            ->where('officer_id', $this->id)
            ->value('current_token');
    }
}
