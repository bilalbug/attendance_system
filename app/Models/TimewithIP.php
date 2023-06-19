<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimewithIP extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'ip_address', 'intime', 'outtime','working_hours', 'location'];
    public function users()
    {
        return $this->belongsTo(User::class, "used_id", "ip");
    }
}
