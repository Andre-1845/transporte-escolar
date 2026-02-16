<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'contact_email',
        'contact_phone',
        'active'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
