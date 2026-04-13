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
Route::get('/app/doctores/{id}/resenas', [ResenaController::class, 'resenasPorDoctor']);
// Catálogo de doctores para la App
Route::get('/app/doctores', function (Illuminate\Http\Request $request) {
    $query = App\Models\Doctor::with('especialidad')
        ->withAvg('resenas', 'estrellas')
        ->withCount('resenas')
        ->where('estado', 'activo');

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

    return $query->get()->map(function($doc) {
        $doc->calificacion_promedio = $doc->resenas_avg_estrellas;
        $doc->total_resenas = $doc->resenas_count;
        return $doc;
    });
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
        Route::post('/app/chat/enviar',          [MensajeController::class, 'enviarMensajeApp']);
      Route::get('/app/chat/mis-conversaciones', [MensajeController::class, 'misConversacionesPaciente']);
    Route::get('/app/chat/{conversacion_id}', [MensajeController::class, 'cargarMensajes']);

    Route::post('/chat/{conversacion_id}/leer', [MensajeController::class, 'marcarComoLeido']); 
    Route::post('/app/chat/{conversacion_id}/leer', [MensajeController::class, 'marcarLeidoPorPaciente']);
  // El doctor marca como leído

    // --- 💻 PANEL DOCTOR (Web) ---
    Route::get('/doctor/dashboard', [DoctorController::class, 'dashboard']); // 🌟 ¡AGREGA ESTA LÍNEA! 🌟
    Route::get('/me', [AuthController::class, 'me']);
    Route::delete('/cuenta', [AuthController::class, 'eliminarCuenta']);
    Route::get('/perfil',            [DoctorController::class, 'show']);
    Route::put('/perfil/actualizar', [AuthController::class, 'actualizarPerfil']);
    Route::post('/perfil/foto',      [AuthController::class, 'subirFoto']);
    
    Route::get('/citas',             [CitaController::class, 'index']);
    Route::post('/citas',        [CitaController::class, 'store']); 
    Route::put('/citas/{id}',        [CitaController::class, 'update']);
    Route::delete('/citas/{id}', [CitaController::class, 'destroy']);
    Route::get('/mensajes',          [MensajeController::class, 'index']);
    Route::get('/mensajes/{id}',     [MensajeController::class, 'show']);
    Route::post('/mensajes/{id}',    [MensajeController::class, 'reply']);

    Route::get('/resenas',           [ResenaController::class, 'index']);
    Route::post('/resenas/{id}/responder', [ResenaController::class, 'responder']);


























   // =======================================================
    // Rutas del Panel Admin (Demo)
    // =======================================================
    Route::prefix('admin')->group(function () {

        // 1. Datos del Dashboard
        Route::get('/dashboard', function () {
            return response()->json([
                'kpis' => ['especialistas_activos' => 12, 'usuarios_total' => 45, 'citas_total' => 128, 'pendientes_aprobacion' => 0],
                'grafica_registros' => ['doctores' => ['Ene'=>2, 'Feb'=>5, 'Mar'=>12], 'pacientes' => ['Ene'=>10, 'Feb'=>20, 'Mar'=>45]],
                'grafica_especialidades' => [['nombre'=>'Cardiología', 'total'=>15], ['nombre'=>'Pediatría', 'total'=>8]],
                'pendientes' => [],
                'actividad' => []
            ]);
        });

        // 2. Lista de Doctores Reales
        Route::get('/doctores', function () {
            $doctores = \App\Models\Doctor::all()->map(function($doc) {
                $doc->especialidad = 'Especialista'; // Simplificado para la vista
                return $doc;
            });
            return response()->json([
                'contadores' => ['todos' => count($doctores), 'aprobados' => count($doctores), 'pendientes' => 0, 'suspendidos' => 0],
                'data' => $doctores,
                'pagina' => 1, 'paginas' => 1, 'total' => count($doctores)
            ]);
        });

        // 3. Lista de Pacientes Reales
        Route::get('/pacientes', function () {
            $pacientes = \App\Models\Paciente::all();
            return response()->json([
                'contadores' => ['total' => count($pacientes), 'activos' => count($pacientes), 'suspendidos' => 0],
                'data' => $pacientes,
                'pagina' => 1, 'paginas' => 1, 'total' => count($pacientes)
            ]);
        });

        // 4. Especialidades Reales
        Route::get('/especialidades', function () {
            return response()->json(\App\Models\Especialidad::all());
        });

        // 5. Estadísticas Simuladas
        Route::get('/estadisticas', function () {
            return response()->json([
                'mini_kpis' => ['calificacion_promedio' => 4.8, 'citas_total' => 128, 'citas_este_mes' => 24, 'tasa_aprobacion' => 98],
                'crecimiento' => [],
                'distribucion' => []
            ]);
        });

        Route::get('/estadisticas/regiones', function () {
            return response()->json([]);
        });

        Route::post('/logout', [AuthController::class, 'logout']);
    }); 
});
