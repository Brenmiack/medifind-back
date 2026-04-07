<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    protected $fillable = [
        'doctor_id',
        'paciente_nombre',
        'estrellas',
        'comentario',
        'respuesta',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
