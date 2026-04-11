<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Authenticatable 
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nombre', 'genero', 'cedula', 'email', 'password', 'telefono',
        'especialidad_id', 'estado', 'verificado', 'ip_registro', 
        'consent_accepted_at', 'activo', 'foto_url',
        'direccion', 'latitud', 'longitud', 'whatsapp', 'descripcion', 'horario', 
        'servicios', 'especialidades_extra' 
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'servicios' => 'array',             
        'especialidades_extra' => 'array',   
    ];
    protected $with = ['especialidad', 'horarios'];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relación con Especialidad
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class);
    }

    // Relación con Horario 
    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    // 👇 AQUÍ ESTÁ LA MAGIA QUE FALTABA 👇
    // Relación: Un doctor tiene muchas citas
    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

public function resenas()
{
    return $this->hasMany(\App\Models\Resena::class, 'doctor_id');
}





}