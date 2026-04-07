<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $fillable = ['conversacion_id', 'emisor', 'contenido'];

    public function conversacion()
    {
        return $this->belongsTo(Conversacion::class);
    }
}
