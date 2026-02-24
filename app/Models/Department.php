<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Department extends Model
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
        return $this->hasMany(User::class, 'department_id', 'id');
    }

    public function visitors()
    {
        return $this->hasMany(Visitor::class, 'department_id', 'id');
    }
}
