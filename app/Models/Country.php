<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries'; // Make sure this is your table name
    protected $fillable = ['name', 'iso2', 'iso3', 'numeric_code'];
    public $timestamps = false; // optional
}