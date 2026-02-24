<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Designation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'slug', 'description', 'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'designation_id', 'id');
    }
}
