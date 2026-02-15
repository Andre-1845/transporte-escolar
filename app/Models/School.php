<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    //
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
