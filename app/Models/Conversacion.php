<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversacion extends Model
{

    protected $table = 'conversaciones';
    protected $fillable = ['doctor_id', 'paciente_id'];

    public function mensajes()
    {
        return $this->hasMany(Mensaje::class);
    }

    public function paciente()
{
    return $this->belongsTo(Paciente::class, 'paciente_id');
}

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
