<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Cita;
use App\Models\Mensaje;

class DoctorController extends Controller
{
    public function dashboard(Request $request)
    {
        $doctor = $request->user();
        return response()->json([
            'saludo' => $this->getSaludo(),
            'doctor' => [
                'nombre' => $doctor->nombre,
                'especialidad' => $doctor->especialidad->nombre ?? 'Sin especialidad',
                'foto' => $doctor->foto_url ?? 'default-avatar.png',
            ],
            'stats' => [
                'vistas_perfil' => $doctor->visualizaciones ?? 0,
                'calificacion' => number_format($doctor->calificacion_promedio ?? 0, 1),
                'mensajes_no_leidos' => Mensaje::where('doctor_id', $doctor->id)->where('leido', false)->count(),
                'citas_pendientes' => Cita::where('doctor_id', $doctor->id)->where('estado', 'pendiente')->count(),
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

// En DoctorController.php (Esta función actualmente no se usa para el perfil web)
    public function update(Request $request)
    {
        $doctor = $request->user();

        $doctor->update($request->only([
            'nombre',
            'telefono', 
            'whatsapp', 
            'direccion', 
            'descripcion', 
            'latitud', 
            'longitud',
            'servicios',
            'especialidades_extra',
            'horario'
        ]));

        if ($request->filled('password')) {
            $doctor->password = \Hash::make($request->password);
            $doctor->save();
        }

        return response()->json([
            'mensaje' => 'Perfil actualizado (desde DoctorController)',
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