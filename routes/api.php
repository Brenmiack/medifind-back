<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\PacienteAuthController;

/*
|--------------------------------------------------------------------------
| MediFind API Routes
|--------------------------------------------------------------------------
*/

// =======================================================
// 🔓 RUTAS PÚBLICAS (Sin Token)
// =======================================================
Route::post('/registro',          [AuthController::class, 'registro']);
Route::post('/login',             [AuthController::class, 'login']);
Route::post('/registro-paciente', [PacienteAuthController::class, 'registroPaciente']);
Route::post('/login-paciente',    [PacienteAuthController::class, 'loginPaciente']);

// Catálogo de doctores para la App
Route::get('/app/doctores', function (Illuminate\Http\Request $request) {
    $query = App\Models\Doctor::with('especialidad')->where('estado', 'activo');
    if ($request->has('especialidad_id')) $query->where('especialidad_id', $request->especialidad_id);
    if ($request->has('nombre')) {
        $busqueda = $request->nombre;
        $query->where(function($q) use ($busqueda) {
            $q->where('nombre', 'LIKE', '%' . $busqueda . '%')
              ->orWhereHas('especialidad', function($qEsp) use ($busqueda) {
                  $qEsp->where('nombre', 'LIKE', '%' . $busqueda . '%');
              });
        });
    }
    return $query->get();
});

Route::get('/app/doctores/{id}', function ($id) {
    return App\Models\Doctor::with(['especialidad', 'horarios', 'citas'])
        ->where('estado', 'activo')->findOrFail($id);
});

Route::get('/app/especialidades', function () {
    return App\Models\Especialidad::all();
});


// =======================================================
// 🔐 RUTAS PROTEGIDAS (Requieren Token)
// =======================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- 📱 PERFIL Y AUTH PACIENTE (App) ---
    Route::get('/user', function (Illuminate\Http\Request $request) { return $request->user(); });
    Route::put('/app/perfil/actualizar', [PacienteAuthController::class, 'actualizarPerfil']);
    Route::post('/app/perfil/foto',      [PacienteAuthController::class, 'subirFoto']);
    Route::post('/app/perfil/token',     [PacienteAuthController::class, 'guardarToken']);
    Route::post('/logout',               [AuthController::class, 'logout']);

    // --- 📅 CITAS Y RESEÑAS (App) ---
    Route::post('/app/citas',            [CitaController::class, 'storeDesdeApp']);
    Route::get('/app/mis-citas',         [CitaController::class, 'misCitasPaciente']);
    Route::post('/app/cancelar-cita/{id}', [CitaController::class, 'destroy']); // <-- AGREGADA PARA EL BOTÓN ROJO
    Route::post('/app/resenas',          [ResenaController::class, 'storeDesdeApp']);
    Route::get('/app/mis-resenas',       [ResenaController::class, 'misResenasPaciente']);

    // --- 💬 CHAT (App y Web) ---
    Route::post('/app/chat/iniciar',         [MensajeController::class, 'iniciarOObtenerChat']);
    Route::get('/app/chat/{conversacion_id}', [MensajeController::class, 'cargarMensajes']);
    Route::post('/app/chat/enviar',          [MensajeController::class, 'enviarMensajeApp']);
    Route::post('/chat/{conversacion_id}/leer', [MensajeController::class, 'marcarComoLeido']); // El doctor marca como leído

    // --- 💻 PANEL DOCTOR (Web) ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/perfil',            [DoctorController::class, 'show']);
    Route::put('/perfil/actualizar', [AuthController::class, 'actualizarPerfil']);
    Route::post('/perfil/foto',      [AuthController::class, 'subirFoto']);
    
    Route::get('/citas',             [CitaController::class, 'index']);
    Route::put('/citas/{id}',        [CitaController::class, 'update']);
    
    Route::get('/mensajes',          [MensajeController::class, 'index']);
    Route::get('/mensajes/{id}',     [MensajeController::class, 'show']);
    Route::post('/mensajes/{id}',    [MensajeController::class, 'reply']);

    Route::get('/resenas',           [ResenaController::class, 'index']);
    Route::post('/resenas/{id}/responder', [ResenaController::class, 'responder']);
});