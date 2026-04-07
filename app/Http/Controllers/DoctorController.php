<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Cita; // Asegúrate de tener estos modelos
use App\Models\Mensaje;

class DoctorController extends Controller
{
    // GET /api/especialista/dashboard (Nuevo: Requisito 3.2.3)
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
            'config' => [
                'perfil_activo' => (bool)$doctor->activo
            ]
        ]);
    }

    // PATCH /api/especialista/estado (Nuevo: Toggle Activo/Inactivo)
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

    // GET /api/perfil
    public function show(Request $request)
    {
        // Cargamos la relación especialidad para que el front tenga el nombre
        return response()->json($request->user()->load('especialidad'));
    }

    // PUT /api/perfil
    public function update(Request $request)
    {
        $doctor = $request->user();

        $request->validate([
            'nombre'        => 'sometimes|string|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/u',
            'cedula'        => 'sometimes|numeric|digits_between:7,8|unique:doctors,cedula,' . $doctor->id,
            'telefono'      => 'sometimes|numeric|digits:10',
            'whatsapp'      => 'sometimes|numeric|digits:10',
            'direccion'     => 'sometimes|string|max:500',
            'descripcion'   => 'sometimes|string|max:1000',
            'tipo_consulta' => 'sometimes|in:Presencial,Online,Ambas',
            'password'      => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $data = $request->except(['password', 'password_confirmation', 'activo']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $doctor->update($data);

        return response()->json([
            'mensaje' => 'Perfil actualizado con éxito',
            'doctor'  => $doctor->load('especialidad'),
        ]);
    }

    // Función auxiliar para el saludo (Requisito UX)
    private function getSaludo() {
        $hora = now()->format('H');
        if ($hora < 12) return "Buenos días";
        if ($hora < 19) return "Buenas tardes";
        return "Buenas noches";
    }
}