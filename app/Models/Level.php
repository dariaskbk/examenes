<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $connection = 'sicore';

    protected $fillable = ['name', 'cycle'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /** Etiqueta legible: "7mo año — Ciclo III" o solo "7mo año" */
    public function getFullLabelAttribute(): string
    {
        return $this->cycle
            ? $this->name . ' — ' . $this->cycle
            : $this->name;
    }
}
