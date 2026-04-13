<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use Carbon\Carbon;

class CitaController extends Controller
{
    private function normalizarNombre(?string $nombre): string
    {
        $nombre = trim((string) $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        return mb_strtolower($nombre, 'UTF-8');
    }

    private function validarConflictosAgenda(int $doctorId, string $fecha, string $hora, ?int $excluirId = null): void
    {
        // Regla 1: No permitir otra cita (no cancelada) en la misma hora
        $q = Cita::where('doctor_id', $doctorId)
            ->whereDate('fecha', $fecha)
            ->whereTime('hora', $hora)
            ->whereIn('estado', ['pendiente', 'aceptada']);
        if ($excluirId) $q->where('id', '!=', $excluirId);
        if ($q->exists()) {
            abort(response()->json([
                'mensaje' => 'Ya hay una cita agendada en esa misma hora.'
            ], 422));
        }

        // Nota: permitimos usar todas las horas marcadas del horario.
        // Solo se bloquea cuando ya existe una cita en la misma hora exacta.
    }

    private function validarUnicidadNombreEstado(int $doctorId, string $fecha, string $estado, string $pacienteNombre, ?int $excluirId = null): void
    {
        $nombreNorm = $this->normalizarNombre($pacienteNombre);

        $q = Cita::where('doctor_id', $doctorId)
            ->whereDate('fecha', $fecha)
            ->where('estado', $estado);
        if ($excluirId) $q->where('id', '!=', $excluirId);

        $citas = $q->get(['id', 'paciente_nombre']);
        foreach ($citas as $c) {
            if ($this->normalizarNombre($c->paciente_nombre) === $nombreNorm) {
                abort(response()->json([
                    'mensaje' => 'Ya existe una cita con el mismo estado para ese paciente en esa fecha.'
                ], 422));
            }
        }
    }

    // GET /api/citas (Carga todas las citas en el calendario y lista)
    public function index(Request $request)
    {
        $doctorId = (int) $request->user()->id;
        $ahora = Carbon::now();

        // Auto-actualiza estados vencidos:
        // pendiente -> cancelada, aceptada -> completada
        $citasVencidas = Cita::where('doctor_id', $doctorId)
            ->whereIn('estado', ['pendiente', 'aceptada'])
            ->get();

        foreach ($citasVencidas as $cita) {
            $fechaHoraCita = Carbon::parse($cita->fecha . ' ' . Carbon::parse($cita->hora)->format('H:i:s'));
            if ($fechaHoraCita->lt($ahora)) {
                // Solo las aceptadas pasan a completada automáticamente
                // Las pendientes NO se cancelan — el doctor decide qué hacer con ellas
                if ($cita->estado === 'aceptada') {
                    $cita->estado = 'completada';
                    $cita->save();
                }
            }
        }

        $citas = Cita::where('doctor_id', $doctorId)
                     ->orderBy('fecha')
                     ->orderBy('hora')
                     ->get();

        return response()->json($citas);
    }

    // POST /api/citas (Crea una cita nueva desde el modal)
    public function store(Request $request)
    {
        $request->validate([
            'paciente_nombre' => 'required|string|max:255',
            'paciente_tel'    => 'nullable|string|max:20',
            'fecha'           => 'required|date',
            'hora'            => 'required|date_format:H:i',
            'notas'           => 'nullable|string|max:500',
        ]);

        $doctorId = (int) $request->user()->id;
        $fecha = $request->fecha;
        $hora  = $request->hora;

        $this->validarConflictosAgenda($doctorId, $fecha, $hora);
        // Estado por defecto: pendiente
        $this->validarUnicidadNombreEstado($doctorId, $fecha, 'pendiente', $request->paciente_nombre);

        $cita = Cita::create([
            'doctor_id'       => $doctorId,
            'paciente_nombre' => $request->paciente_nombre,
            'paciente_tel'    => $request->paciente_tel,
            'fecha'           => $request->fecha,
            'hora'            => $request->hora,
            'notas'           => $request->notas,
            'estado'          => 'pendiente', // Por defecto
        ]);

        return response()->json(['mensaje' => 'Cita agendada', 'cita' => $cita], 201);
    }

    // PUT /api/citas/{id} (Actualiza datos o cambia el estado a completada/cancelada)
    public function update(Request $request, $id)
    {
        $cita = Cita::where('id', $id)
                    ->where('doctor_id', $request->user()->id)
                    ->firstOrFail();

        $request->validate([
            'paciente_nombre' => 'sometimes|required|string|max:255',
            'paciente_tel'    => 'sometimes|nullable|string|max:20',
            'fecha'           => 'sometimes|required|date',
            'hora'            => 'sometimes|required|date_format:H:i',
            'notas'           => 'sometimes|nullable|string|max:500',
            'estado'          => 'sometimes|required|in:pendiente,aceptada,completada,cancelada',
        ]);

        $doctorId = (int) $request->user()->id;

        $nuevoFecha = $request->has('fecha') ? $request->fecha : $cita->fecha;
        $nuevoHora  = $request->has('hora') ? $request->hora : Carbon::parse($cita->hora)->format('H:i');
        $nuevoEstado = $request->has('estado') ? $request->estado : $cita->estado;
        $nuevoNombre = $request->has('paciente_nombre') ? $request->paciente_nombre : $cita->paciente_nombre;

        // Si cambia fecha/hora (o viene estado), validar reglas de agenda (salvo que se cancele)
        if ($nuevoEstado !== 'cancelada') {
            $this->validarConflictosAgenda($doctorId, $nuevoFecha, $nuevoHora, (int) $cita->id);
        }

        // Regla: no duplicar mismo estado+nombre en misma fecha
        $this->validarUnicidadNombreEstado($doctorId, $nuevoFecha, $nuevoEstado, $nuevoNombre, (int) $cita->id);

        // Regla específica: no permitir ACEPTAR si ya existe otra ACEPTADA en esa misma hora
        if ($nuevoEstado === 'aceptada') {
            $hayAceptada = Cita::where('doctor_id', $doctorId)
                ->whereDate('fecha', $nuevoFecha)
                ->whereTime('hora', $nuevoHora)
                ->where('estado', 'aceptada')
                ->where('id', '!=', $cita->id)
                ->exists();
            if ($hayAceptada) {
                return response()->json([
                    'mensaje' => 'No puedes aceptar: ya hay otra cita aceptada a esa misma hora.'
                ], 422);
            }
        }

        $cita->update($request->only([
            'paciente_nombre', 'paciente_tel', 'fecha', 'hora', 'notas', 'estado'
        ]));

        // ==============================================================
        // 🔔 MAGIA DE NOTIFICACIONES PUSH AL ACEPTAR CITA
        // ==============================================================
        if ($nuevoEstado === 'aceptada' && $cita->paciente_id != null) {
            
            // Buscamos al paciente dueño de esta cita
            $paciente = \App\Models\Paciente::find($cita->paciente_id);

            // Verificamos si existe y si tiene su Gafete (Token) guardado
            if ($paciente && $paciente->expo_push_token) {
                
                $mensaje = [
                    'to' => $paciente->expo_push_token,
                    'sound' => 'default',
                    'title' => '¡Cita Confirmada! ✅',
                    'body' => 'Tu cita para el ' . $nuevoFecha . ' a las ' . $nuevoHora . ' ha sido aceptada.',
                    'data' => ['cita_id' => $cita->id]
                ];

                // El "Cartero" de Laravel mandando el paquete a Expo
                $ch = curl_init('https://exp.host/--/api/v2/push/send');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Accept-encoding: gzip, deflate',
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mensaje));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
        // ==============================================================

        return response()->json(['mensaje' => 'Cita actualizada', 'cita' => $cita]);
    }
    // DELETE /api/citas/{id} (Borra la cita desde el botón rojo)
    public function destroy(Request $request, $id)
    {
        $cita = Cita::where('id', $id)
                    ->where('doctor_id', $request->user()->id)
                    ->firstOrFail();

        $cita->delete();

        return response()->json(['mensaje' => 'Cita eliminada']);
    }
public function cancelarDesdeApp(Request $request, $id)
{
    $cita = Cita::where('id', $id)
                ->where('paciente_id', $request->user()->id)
                ->where('estado', 'pendiente')
                ->firstOrFail();

    $cita->estado = 'cancelada';
    $cita->save();

    return response()->json(['mensaje' => 'Cita cancelada correctamente']);
}

    // ==============================================================
    // NUEVA FUNCIÓN EXCLUSIVA PARA LA APP MÓVIL
    // ==============================================================
   public function storeDesdeApp(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|integer',
            'fecha'     => 'required|date',
            'hora'      => 'required|date_format:H:i',
            'notas'     => 'nullable|string|max:500',
        ]);

        // 🕵️‍♂️ EXTRAEMOS AL PACIENTE DEL TOKEN
        $paciente = $request->user();

        $cita = Cita::create([
            'doctor_id'       => $request->doctor_id,
            'paciente_id'     => $paciente->id,              // ¡ID real!
            'paciente_nombre' => $paciente->nombre,          // ¡Nombre real!
            'paciente_tel'    => $paciente->telefono ?? '',  // ¡Teléfono real!
            'fecha'           => $request->fecha,
            'hora'            => $request->hora,
            'notas'           => $request->notas,
            'estado'          => 'pendiente', 
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Cita agendada correctamente', 
            'cita' => $cita
        ], 201);
    }
// Obtener las citas del paciente logueado (App Móvil)
    public function misCitasPaciente(Request $request)
    {
        $pacienteId = $request->user()->id;

        // Traemos las citas con la información del doctor cargada
        $citas = Cita::with('doctor') 
            ->where('paciente_id', $pacienteId)
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();

        return response()->json($citas);
    }



}