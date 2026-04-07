<?php

use Illuminate\Support\Facades\Route;
use App\Models\Doctor; // <-- ¡Lo movimos aquí arriba!

Route::get('/', function () {
    return view('welcome');
});

// Nuestra ruta del Mesero
Route::get('/api/doctores', function () {
    return Doctor::with('especialidad')->get();
});