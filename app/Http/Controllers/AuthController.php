<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\Doctor;

class AuthController 
{
    // POST /api/registro
    // POST /api/registro
public function registro(Request $request)
{
    // 1. Validación de campos del formulario
    $request->validate([
        'nombre'             => 'required|string|max:100',
        'email'              => 'required|email|unique:doctors,email',
        'cedula'             => 'required|numeric|unique:doctors,cedula',
        'telefono'           => 'required|numeric|unique:doctors,telefono',
        'password'           => 'required|min:8|confirmed',
        'especialidad_id'    => 'required',
        'nueva_especialidad' => 'exclude_unless:especialidad_id,otra|required|string|max:100',
        'genero'             => 'required|in:M,F',
    ], [
        'nombre.required'             => 'El nombre es obligatorio.',
        'nombre.string'               => 'El nombre debe ser texto.',
        'nombre.max'                  => 'El nombre no debe exceder 100 caracteres.',
        'email.required'              => 'El correo es obligatorio.',
        'email.email'                 => 'Ingresa un correo válido (ej. nombre@gmail.com).',
        'email.unique'                => 'Este correo ya está registrado.',
        'cedula.required'             => 'La cédula es obligatoria.',
        'cedula.numeric'              => 'La cédula debe contener solo números.',
        'cedula.unique'               => 'Esta cédula ya está registrada.',
        'telefono.required'           => 'El teléfono es obligatorio.',
        'telefono.numeric'            => 'El teléfono debe contener solo números.',
        'telefono.unique'             => 'Este número de teléfono ya está registrado.',
        'password.required'           => 'La contraseña es obligatoria.',
        'password.min'                => 'La contraseña debe tener mínimo 8 caracteres.',
        'password.confirmed'          => 'La confirmación de contraseña no coincide.',
        'especialidad_id.required'    => 'Selecciona una especialidad.',
        'nueva_especialidad.required' => 'Escribe el nombre de tu especialidad.',
        'nueva_especialidad.string'   => 'El nombre de la especialidad debe ser texto.',
        'nueva_especialidad.max'      => 'El nombre de la especialidad no debe exceder 100 caracteres.',
        'genero.required'             => 'Selecciona tu género.',
        'genero.in'                   => 'El género debe ser M o F.',
    ]);

    // 2. Lógica para "Otra" especialidad
    $idFinal = $request->especialidad_id;
    if ($request->especialidad_id === 'otra') {
        $nueva = \App\Models\Especialidad::firstOrCreate([
            'nombre' => ucfirst(strtolower($request->nueva_especialidad))
        ]);
        $idFinal = $nueva->id;
    }

    // 3. Crear el Doctor
    $doctor = \App\Models\Doctor::create([
        'nombre'          => $request->nombre,
        'genero'          => $request->genero,
        'email'           => strtolower(trim($request->email)),
        'cedula'          => $request->cedula,
        'telefono'        => $request->telefono,
        'password'        => Hash::make($request->password),
        'especialidad_id' => $idFinal,
        'estado'          => 'activo',
    ]);

    return response()->json([
        'mensaje' => 'Doctor registrado con éxito',
    ], 201);
}



    
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $doctor = Doctor::where('email', $request->email)->first();

        if (!$doctor || !Hash::check($request->password, $doctor->password)) {
            return response()->json([
                'mensaje' => 'Correo o contraseña incorrectos',
            ], 401);
        }

        if ($doctor->estado === 'suspendido') {
            return response()->json([
                'mensaje' => 'Tu cuenta ha sido suspendida. Contacta a soporte.',
            ], 403);
        }

        $token = $doctor->createToken('medifind')->plainTextToken;

        return response()->json([
            'token'  => $token,
            'doctor' => $doctor,
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Sesión cerrada']);
    }

    // GET /api/me
    // En AuthController.php
public function me(Request $request)
{
    // Carga especialidad y horarios para que el configurador los muestre al entrar
    return response()->json($request->user()->load(['especialidad', 'horarios']));
}   

    // DELETE /api/cuenta
    public function eliminarCuenta(Request $request)
{
    $request->validate(['password' => 'required|string']);

    if (!Hash::check($request->password, $request->user()->password)) {
        return response()->json(['mensaje' => 'Contraseña incorrecta.'], 403);
    }

    $user = $request->user();
    $user->tokens()->delete();
    $user->estado = 'suspendido';
    $user->save();

    return response()->json(['mensaje' => 'Cuenta suspendida correctamente']);
}

    // PUT /api/perfil/actualizar
    public function actualizarPerfil(Request $request)
    {
        $doctor = $request->user();

        // 1. Validamos
        $request->validate([
            'telefono'               => 'nullable|string|max:10',
            'whatsapp'               => 'nullable|string|max:10',
            'direccion'              => 'nullable|string|max:150',
            'descripcion'            => 'nullable|string|max:500',
            'horario'                => 'nullable|string|max:300',
            'servicios'              => 'nullable|array',
            'especialidades_extra'   => 'nullable|array',
            'horarios'               => 'nullable|array',
            'horarios.*.dia'         => 'required_with:horarios|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'horarios.*.hora_inicio' => 'required_with:horarios|date_format:H:i',
            'horarios.*.hora_fin'    => 'required_with:horarios|date_format:H:i',
        ]);

        // 2. Asignación masiva estándar (sin lat y lng)
        $doctor->fill([
            'telefono'             => $request->telefono,
            'whatsapp'             => $request->whatsapp,
            'direccion'            => $request->direccion,
            'descripcion'          => $request->descripcion,
            'horario'              => $request->horario,
            'servicios'            => $request->servicios,
            'especialidades_extra' => $request->especialidades_extra,
        ]);

        // 3. Asignación directa a prueba de mapas (recortando los decimales para HeidiSQL)
        if ($request->has('latitud') && $request->latitud != null) {
            $doctor->latitud = round((float) $request->latitud, 8);
        }
        
        if ($request->has('longitud') && $request->longitud != null) {
            $doctor->longitud = round((float) $request->longitud, 8);
        }

        $doctor->save();

        // 4. Guardamos horarios en la tabla horarios (borramos y reinsertamos)
        if ($request->has('horarios')) {
            \App\Models\Horario::where('doctor_id', $doctor->id)->delete();
            foreach ($request->horarios as $h) {
                \App\Models\Horario::create([
                    'doctor_id'   => $doctor->id,
                    'dia'         => $h['dia'],
                    'hora_inicio' => $h['hora_inicio'],
                    'hora_fin'    => $h['hora_fin'],
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente',
            'doctor'  => $doctor->refresh()->load(['especialidad', 'horarios'])
        ]);
    }

public function subirFoto(Request $request)
    {
        $request->validate([
            'foto' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'min:10',       // Mínimo 10 KB (Evita archivos corruptos o vacíos)
                'max:2048',     // Máximo 2048 KB (2 Megabytes)
                'dimensions:min_width=200,min_height=200,max_width=2000,max_height=2000' // Dimensiones en píxeles
            ]
        ], [
            // Mensajes de error personalizados en español
            'foto.max' => 'La imagen es muy pesada. El máximo es 2MB.',
            'foto.min' => 'La imagen es muy pequeña o está corrupta.',
            'foto.dimensions' => 'La imagen debe medir entre 200x200 y 2000x2000 píxeles.',
            'foto.mimes' => 'Solo se permiten imágenes JPG o PNG.',
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.required' => 'Debes seleccionar una imagen.',
        ]);

        $doctor = $request->user();
        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');

            // Guardar en /public para que sea accesible sin storage:link
            $destino = public_path('fotos_perfil');
            if (!is_dir($destino)) {
                @mkdir($destino, 0755, true);
            }

            $nombre = 'doctor_' . $doctor->id . '_' . time() . '.' . $ext;
            $archivo->move($destino, $nombre);

            $doctor->foto_url = asset('fotos_perfil/' . $nombre);
            $doctor->save();

            return response()->json([
                'mensaje' => 'Foto actualizada con éxito',
                'foto_url' => $doctor->foto_url
            ]);
        }

        return response()->json(['mensaje' => 'No se recibió imagen'], 400);
    }



}
