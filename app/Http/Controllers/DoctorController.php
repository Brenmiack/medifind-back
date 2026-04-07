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

public function update(Request $request)
    {
        $doctor = $request->user();

        // 1. Guardado DIRECTO a la tabla (Ignora fillables, ignores caches, ignora todo)
        // Usamos el Query Builder de Laravel
        $query = \DB::table('doctors')->where('id', $doctor->id)->update([
            'nombre'      => $request->nombre,
            'telefono'    => $request->telefono,
            'whatsapp'    => $request->whatsapp,
            'direccion'   => $request->direccion,
            'descripcion' => $request->descripcion,
            'latitud'     => $request->latitud,  // Aquí va el número directo
            'longitud'    => $request->longitud, // Aquí va el número directo
            'updated_at'  => now()
        ]);

        // 2. Si cambió la contraseña, la actualizamos aparte
        if ($request->filled('password')) {
            \DB::table('doctors')->where('id', $doctor->id)->update([
                'password' => \Hash::make($request->password)
            ]);
        }

        return response()->json([
            // Este mensaje nos dirá si la base de datos aceptó el cambio (1 = sí, 0 = no cambió nada)
            'mensaje' => '¡Perfil actualizado con éxito!',
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