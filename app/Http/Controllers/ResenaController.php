<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resena;

class ResenaController extends Controller
{
    // =======================================================
    // 📱 PARTE 1: LO QUE USA LA APP MÓVIL (PACIENTE)
    // =======================================================

    public function storeDesdeApp(Request $request)
    {
        $request->validate([
            'doctor_id'  => 'required|integer',
            'cita_id'    => 'required|integer',
            'estrellas'  => 'required|integer|min:1|max:5',
            'comentario' => 'required|string|max:500'
        ]);

        $paciente = $request->user();

        $resena = Resena::create([
            'doctor_id'       => $request->doctor_id,
            'paciente_nombre' => $paciente->nombre . ' ' . $paciente->paterno,
            'estrellas'       => $request->estrellas,
            'comentario'      => $request->comentario,
        ]);

        // Marcamos la cita para que NO se pueda volver a calificar
        $cita = \App\Models\Cita::find($request->cita_id);
        if ($cita) {
            $cita->tiene_resena = 1;
            $cita->save();
        }

        return response()->json(['mensaje' => '¡Gracias por tu reseña!']);
    }

    // Obtiene el historial de reseñas que el paciente ha escrito
    public function misResenasPaciente(Request $request)
    {
        // Concatenamos el nombre del paciente tal como se guarda en la tabla 'resenas'
        $paciente = $request->user();
        $nombreCompleto = $paciente->nombre . ' ' . $paciente->paterno;

        $resenas = Resena::with('doctor') // Asumiendo que Resena tiene una relación 'doctor'
                         ->where('paciente_nombre', $nombreCompleto)
                         ->orderByDesc('created_at')
                         ->get();

        return response()->json($resenas);
    }

    // =======================================================
    // 💻 PARTE 2: LO QUE USA EL PANEL WEB (DOCTOR) API
    // =======================================================

    // GET /api/resenas (Carga las reseñas en la web)
    public function index(Request $request)
    {
        $resenas = Resena::where('doctor_id', $request->user()->id)
                         ->orderByDesc('created_at')
                         ->get();

        return response()->json($resenas);
    }

    // POST /api/resenas/{id}/responder (El doctor responde)
    public function responder(Request $request, $id)
    {
        $request->validate([
            'respuesta' => 'required|string|max:500',
        ]);

        $resena = Resena::where('id', $id)
                        ->where('doctor_id', $request->user()->id)
                        ->firstOrFail();

        $resena->update(['respuesta' => $request->respuesta]);

        return response()->json(['mensaje' => 'Respuesta guardada', 'resena' => $resena]);
    }
    public function resenasPorDoctor($id)
{
    $resenas = \App\Models\Resena::where('doctor_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($resenas);
}
}