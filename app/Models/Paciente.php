<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Paciente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Aquí le decimos a Laravel en qué tabla guardar
    protected $table = 'pacientes';

    // Estos son los únicos campos que permitimos que se llenen desde la app
protected $fillable = [
    'nombre',
    'paterno',  // <-- Nuevo
    'materno',  // <-- Nuevo
    'email',
    'password',
    'telefono',
    'direccion',
    'foto_url',
    'expo_push_token'
    // ... las que ya tenías
];

    // Ocultamos la contraseña para que nunca viaje por accidente
    protected $hidden = [
        'password',
        'remember_token',
    ];
}