<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Paciente; 

class PacienteAuthController extends Controller
{
   // --- REGISTRO CORREGIDO ---
    public function registroPaciente(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:150',
            'paterno'  => 'required|string|max:150',  // 🌟 Agregamos validación
            'materno'  => 'nullable|string|max:150', // 🌟 Agregamos validación
            'email'    => 'required|email|unique:pacientes,email',
            'telefono' => 'required|numeric|unique:pacientes,telefono',
            'password' => 'required|min:6',
        ], [
            'email.unique' => 'Este correo ya está registrado.',
            'telefono.unique' => 'Este número de teléfono ya está registrado.',
        ]);

        $paciente = Paciente::create([
            'nombre'   => $request->nombre,
            'paterno'  => $request->paterno,  // 🌟 ¡ESTO ES LO QUE FALTABA!
            'materno'  => $request->materno,  // 🌟 ¡Y ESTO TAMBIÉN!
            'email'    => strtolower(trim($request->email)),
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password), 
        ]);

        return response()->json([
            'mensaje'  => 'Paciente registrado con éxito',
            'paciente' => $paciente
        ], 201);
    }

    // --- LOGIN CON LAS 3 ALARMAS ---
    public function loginPaciente(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $paciente = \App\Models\Paciente::where('email', $request->email)->first();

        // 🚨 ALARMA 1
        if (!$paciente) {
            return response()->json([
                'mensaje' => 'ERROR 1: El correo que ingresaste no es el correcto'
            ], 401);
        }

        // 🚨 ALARMA 2
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $paciente->password)) {
            return response()->json([
                'mensaje' => 'ERROR 2: La contraseña no coincide con la registrada'
            ], 401);
        }

        // 🚨 ALARMA 3
        try {
            $token = $paciente->createToken('paciente_token')->plainTextToken;
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'ERROR 3: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'token' => $token,
            'paciente' => $paciente
        ]);
    }

    // --- ACTUALIZAR PERFIL DEL PACIENTE ---
    public function actualizarPerfil(Request $request)
    {
        $paciente = $request->user();

        $request->validate([
            'nombre'   => 'required|string|max:150',
            'paterno'  => 'required|string|max:150',
            'materno'  => 'nullable|string|max:150',
            'telefono' => 'required|string|max:10',
            'direccion' => 'nullable|string|max:255',
            'password' => 'nullable|min:6',
        ]);

        $paciente->nombre = $request->nombre;
        $paciente->paterno = $request->paterno;
        $paciente->materno = $request->materno;
        $paciente->telefono = $request->telefono;
        $paciente->direccion = $request->direccion;

        if ($request->filled('password')) {
            $paciente->password = Hash::make($request->password);
        }

        $paciente->save();

        return response()->json([
            'mensaje'  => 'Perfil actualizado con éxito',
            'paciente' => $paciente
        ]);
    }

    // --- SUBIR FOTO DE PERFIL DEL PACIENTE ---
    public function subirFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $paciente = $request->user();

        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
            
            $destino = public_path('fotos_perfil/pacientes');
            if (!is_dir($destino)) {
                @mkdir($destino, 0755, true);
            }

            $nombre = 'paciente_' . $paciente->id . '_' . time() . '.' . $ext;
            $archivo->move($destino, $nombre);

            $paciente->foto_url = asset('fotos_perfil/pacientes/' . $nombre);
            $paciente->save();

            return response()->json([
                'mensaje' => 'Foto actualizada con éxito',
                'foto_url' => $paciente->foto_url
            ]);
        }

        return response()->json(['mensaje' => 'No se recibió imagen'], 400);
    }

    // --- GUARDAR TOKEN DE NOTIFICACIONES ---
    public function guardarToken(Request $request)
    {
        $request->validate([
            'expo_push_token' => 'required|string'
        ]);

        $paciente = $request->user();
        $paciente->expo_push_token = $request->expo_push_token;
        $paciente->save();

        return response()->json(['mensaje' => 'Token guardado correctamente']);
    }
}