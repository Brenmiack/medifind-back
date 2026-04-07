<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'doctor_id',
        'dia',
        'hora_inicio',
        'hora_fin',
    ];

    // Relación: un horario pertenece a un doctor
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}