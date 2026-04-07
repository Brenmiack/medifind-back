<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'doctor_id',
        'paciente_id',
        'paciente_nombre',
        'paciente_tel',
        'fecha',
        'hora',
        'notas',
        'estado',
        'tiene_resena'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}