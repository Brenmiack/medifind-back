<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    // Le decimos a Laravel cómo se llama su tabla
    protected $table = 'especialidades';
    protected $fillable = ['nombre'];
}