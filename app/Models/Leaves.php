<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaves extends Model
{
    use HasFactory;

    protected $fillable = ['officer_id', 'department_id', 'business_id','start_date', 'end_date'];
    protected $table = 'leaves';

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function officer()
    {
        return $this->belongsTo(Officer::class);
    }
}
