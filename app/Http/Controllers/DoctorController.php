<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Cita;
use App\Models\Mensaje;
use App\Models\Resena; // 🌟 Agregamos la importación de Resena
use App\Models\Doctor; // 🌟 Agregamos la importación de Doctor

class DoctorController extends Controller
{
    public function dashboard(Request $request)
    {
        // Obtenemos al doctor con su especialidad cargada para evitar errores
        $doctor = $request->user()->load('especialidad');

        // 1. Calculamos el promedio real y el conteo
        // Usamos la clase Resena directamente gracias al "use" de arriba
        $promedio = Resena::where('doctor_id', $doctor->id)->avg('estrellas') ?? 0;
        $totalResenas = Resena::where('doctor_id', $doctor->id)->count();

        // 2. Traemos las 3 citas más próximas (aceptadas)
        $proximasCitas = Cita::where('doctor_id', $doctor->id)
            ->where('estado', 'aceptada')
            ->where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->take(3)
            ->get();

        // 3. Traemos las 3 reseñas más recientes (Más recientes arriba)
        $resenasRecientes = Resena::where('doctor_id', $doctor->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return response()->json([
            'saludo' => $this->getSaludo(),
            'doctor' => [
                'nombre' => $doctor->nombre,
                'especialidad' => $doctor->especialidad->nombre ?? 'Sin especialidad',
                'foto' => $doctor->foto_url,
            ],
            'stats' => [
                'vistas_perfil' => $doctor->visualizaciones ?? 0,
                'calificacion' => number_format($promedio, 1), // Esto manda "4.5" por ejemplo
                'total_resenas' => $totalResenas,
                // ✅ Correcto: busca a través de las conversaciones del doctor
'mensajes_no_leidos' => Mensaje::whereHas('conversacion', function($q) use ($doctor) {
    $q->where('doctor_id', $doctor->id);
})->where('emisor', 'paciente')->where('leido', 0)->count(),
                'citas_pendientes' => Cita::where('doctor_id', $doctor->id)->where('estado', 'pendiente')->count(),
                'resenas_sin_respuesta' => Resena::where('doctor_id', $doctor->id)  // ← AGREGAR
        ->whereNull('respuesta')->count(),
            ],
            'listas' => [
                'proximas_citas' => $proximasCitas,
                'resenas_recientes' => $resenasRecientes
            ],
            'config' => ['perfil_activo' => (bool)$doctor->activo]
        ]);
    }

    public function toggleEstado(Request $request)
    {
        $doctor = $request->user();
        $doctor->activo = !$doctor->activo;
        $doctor->save();
        return response()->json([
            'mensaje' => $doctor->activo ? 'Perfil activado' : 'Perfil desactivado',
            'activo' => $doctor->activo
        ]);
    }

    public function show(Request $request)
    {
        return response()->json($request->user()->load('especialidad'));
    }

    public function update(Request $request)
    {
        $doctor = $request->user();
        $doctor->update($request->only([
            'nombre', 'telefono', 'whatsapp', 'direccion', 
            'descripcion', 'latitud', 'longitud', 'servicios',
            'especialidades_extra', 'horario'
        ]));

        if ($request->filled('password')) {
            $doctor->password = Hash::make($request->password);
            $doctor->save();
        }

        return response()->json([
            'mensaje' => 'Perfil actualizado',
            'doctor'  => $doctor->refresh()->load('especialidad'),
        ]);
    }

    private function getSaludo() {
        $hora = now()->format('H');
        if ($hora < 12) return "Buenos días";
        if ($hora < 19) return "Buenas tardes";
        return "Buenas noches";
    }
}