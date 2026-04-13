<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mensaje;
use App\Models\Conversacion;

class MensajeController extends Controller
{
    // =======================================================
    // 💻 PARTE 1: PANEL WEB (DOCTOR)
    // =======================================================

    // GET /api/mensajes  → lista de conversaciones del doctor
    public function index(Request $request)
    {
        $conversaciones = Conversacion::where('doctor_id', $request->user()->id)
            ->with(['paciente:id,nombre,paterno,foto_url,telefono', 'mensajes'])
            ->withCount(['mensajes as no_leidos' => function($query) {
                $query->where('emisor', 'paciente')->where('leido', 0);
            }])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($conversaciones);
    }

    // GET /api/mensajes/{id}  → mensajes de una conversación
    public function show(Request $request, $id)
    {
        $conversacion = Conversacion::where('id', $id)
            ->where('doctor_id', $request->user()->id)
            ->with('paciente')
            ->firstOrFail();

        $mensajes = Mensaje::where('conversacion_id', $id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'conversacion' => $conversacion,
            'mensajes'     => $mensajes,
        ]);
    }

    public function marcarLeidoPorPaciente(Request $request, $conversacion_id)
{
    Mensaje::where('conversacion_id', $conversacion_id)
           ->where('emisor', 'doctor')
           ->where('leido', 0)
           ->update(['leido' => 1]);

    return response()->json(['success' => true]);
}



    // POST /api/mensajes/{id}  → el doctor responde
    public function reply(Request $request, $id)
    {
        $request->validate([
            'contenido' => 'required|string|max:1000',
        ]);

        $conversacion = Conversacion::where('id', $id)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $mensaje = Mensaje::create([
            'conversacion_id' => $id,
            'emisor'          => 'doctor',
            'contenido'       => $request->contenido,
        ]);

        $conversacion->touch(); 

        return response()->json(['mensaje' => 'Enviado', 'data' => $mensaje], 201);
    }

    // 👇 ESTA ES LA FUNCIÓN NUEVA QUE AGREGAMOS PARA EL DOCTOR 👇
    public function marcarComoLeido($conversacion_id)
    {
        Mensaje::where('conversacion_id', $conversacion_id)
               ->where('emisor', 'paciente')
               ->update(['leido' => 1]);

        return response()->json(['success' => true]);
    }

    // =======================================================
    // 📱 PARTE 2: APP MÓVIL (PACIENTE)
    // =======================================================

    public function iniciarOObtenerChat(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|integer'
        ]);

        $paciente = $request->user();
        $paciente_id = $paciente->id;

        $conversacion = Conversacion::firstOrCreate(
            [
                'doctor_id' => $request->doctor_id,
                'paciente_id' => $paciente_id,
            ],
            [
                'paciente_nombre' => trim($paciente->nombre . ' ' . ($paciente->paterno ?? '')),
                'paciente_tel' => $paciente->telefono,
            ]
        );

        return response()->json($conversacion);
    }

    public function cargarMensajes(Request $request, $conversacion_id)
    {
        $mensajes = Mensaje::where('conversacion_id', $conversacion_id)
                           ->orderBy('created_at', 'asc')
                           ->get();

        return response()->json($mensajes);
    }

    public function enviarMensajeApp(Request $request)
    {
        $request->validate([
            'conversacion_id' => 'required|integer',
            'contenido'       => 'required|string'
        ]);

        $mensaje = Mensaje::create([
            'conversacion_id' => $request->conversacion_id,
            'emisor'          => 'paciente', 
            'contenido'       => $request->contenido,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => $mensaje
        ]);
    }


public function misConversacionesPaciente(Request $request)
{
    $pacienteId = $request->user()->id;

    $conversaciones = \App\Models\Conversacion::with(['doctor', 'mensajes' => function($q) {
            $q->latest()->limit(1);
        }])
        ->where('paciente_id', $pacienteId)
        ->latest('updated_at')
        ->get()
        ->map(function($conv) use ($pacienteId) {
            $conv->no_leidos_paciente = $conv->mensajes()
                ->where('emisor', 'doctor')
                ->where('leido', false)
                ->count();
            return $conv;
        });

    return response()->json($conversaciones);
}



}
