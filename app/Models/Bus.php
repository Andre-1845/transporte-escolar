<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;

class Bus extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'plate',
        'model',
        'capacity',
        'active'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
