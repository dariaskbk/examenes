<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    protected $connection = 'sicore';

    protected $fillable = ['year', 'status'];

    public static function active(): ?self
    {
        return static::where('status', 1)->first();
    }
}
