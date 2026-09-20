<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresiones de TD-16: el módulo de administración de usuarios sin validación
 * de servidor, con contraseña fija y con borrado físico en cascada.
 *
 * Las tres consecuencias que estas pruebas fijan:
 *
 *  - Elevación de privilegios (NFR1). El selector de rol no ofrecía
 *    `asistente`, así que al editar un asistente el navegador enviaba la
 *    primera opción del desplegable —`admin`— y el controlador la aceptaba sin
 *    comprobarla contra el enum de la columna.
 *  - Contraseña compartida (FR3.6). Toda alta se guardaba con el hash de
 *    `password123`, la misma para todos los usuarios creados desde el panel.
 *  - Pérdida de datos clínicos (NFR2). `destroy()` borraba la fila de
 *    `usuarios`, y `fk_pacientes_usuario` la arrastraba en cascada junto con la
 *    ficha del paciente.
 *
 * Prerrequisito: `bash database/testing/bootstrap-test-db.sh` con el entorno
 * Docker en marcha. Ver unit-test-instructions.md.
 *
 * Aislamiento: `DatabaseTransactions`, nunca `RefreshDatabase`. Las tablas del
 * dominio y sus claves foráneas vienen del volcado, no de migraciones de
 * Laravel, y son justo lo que estas pruebas necesitan observar.
 */
class UsuariosAdminTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Crea un usuario del rol indicado. App\Models\Usuario es el proveedor del
     * guard `web`, así que el que tenga rol `admin` sirve para `actingAs()` y
     * atraviesa los middleware `auth` y `role:admin`.
     */
    private function crearUsuario(string $rol, array $valores = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre'        => 'Persona',
            'apellido'      => 'De Pruebas',
            'email'         => 'usuario.pruebas.' . Str::random(8) . '@example.test',
            'rol'           => $rol,
            'telefono'      => '555-0000',
            'activo'        => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ], $valores, ['rol' => $rol]));
    }

    /**
     * El formulario de alta y edición de usuarios envía siempre estos campos.
     */
    private function payloadFormulario(array $valores = []): array
    {
        return array_merge([
            'nombre'   => 'Nuevo',
            'apellido' => 'Usuario',
            'email'    => 'nuevo.' . Str::random(8) . '@example.test',
            'telefono' => '555-1111',
            'rol'      => 'cliente',
            'activo'   => '1',
        ], $valores);
    }

    /**
     * Monta el historial clínico completo de un paciente: su ficha, un dentista
     * con su fila en `personal`, una cita y el expediente de esa cita.
     *
     * Devuelve los identificadores creados. Los inserts van por `DB::table`
     * para que el fixture no dependa de ningún controlador bajo prueba.
     *
     * @return array{paciente: string, cita: string, expediente: string}
     */
    private function crearHistorialClinico(string $usuarioClienteId): array
    {
        $dentista   = $this->crearUsuario('dentista');
        $personalId = (string) Str::uuid();

        DB::table('personal')->insert([
            'id'         => $personalId,
            'usuario_id' => $dentista->id,
            'tipo'       => 'dentista',
        ]);

        $pacienteId = (string) Str::uuid();
        DB::table('pacientes')->insert([
            'id'               => $pacienteId,
            'usuario_id'       => $usuarioClienteId,
            'fecha_nacimiento' => '1990-01-01',
            'genero'           => 'femenino',
            'alergias'         => 'penicilina',
        ]);

        $citaId = (string) Str::uuid();
        DB::table('citas')->insert([
            'id'           => $citaId,
            'paciente_id'  => $pacienteId,
            'dentista_id'  => $personalId,
            'fecha_hora'   => '2026-04-17 09:00:00',
            'duracion_min' => 45,
            'estado'       => 'completada',
        ]);

        $expedienteId = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id'          => $expedienteId,
            'paciente_id' => $pacienteId,
            'cita_id'     => $citaId,
            'dentista_id' => $personalId,
            'diagnostico' => 'Caries oclusal en primer molar inferior derecho.',
            'tratamiento' => 'Obturación con resina.',
        ]);

        return [
            'paciente'   => $pacienteId,
            'cita'       => $citaId,
            'expediente' => $expedienteId,
        ];
    }

    /**
     * FR3.3 y NFR1 — regresión de la elevación de privilegios de TD-16.
     *
     * Dos escenarios, porque el defecto tenía dos mitades:
     *
     *  1. El formulario de edición de un asistente debe ofrecer su propio rol y
     *     traerlo preseleccionado. Sin la opción `asistente` el navegador
     *     seleccionaba la primera del desplegable (`admin`) y la enviaba al
     *     guardar: esa era la vía real de escalada.
     *  2. Una petición que no trae el rol —el caso del brief: editar enviando
     *     solo un teléfono nuevo— se rechaza en la validación de servidor y deja
     *     el rol intacto. Antes no había validación ninguna.
     */
    public function test_el_rol_asistente_sobrevive_a_una_edicion_parcial(): void
    {
        $admin     = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');

        // 1. El desplegable ofrece `asistente` y lo trae marcado.
        $formulario = $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $asistente->id));

        $formulario->assertOk();

        $this->assertMatchesRegularExpression(
            '/<option\s+value="asistente"[^>]*\sselected[^>]*>/',
            $formulario->getContent(),
            'El selector de rol no preselecciona "asistente": el navegador enviaría la primera opción '
            . 'del desplegable y convertiría al asistente en administrador al guardar.'
        );

        // 2. Una edición parcial que omite el rol se rechaza y no lo toca.
        $respuesta = $this->actingAs($admin)
            ->from(route('admin.usuarios.edit', $asistente->id))
            ->put(route('admin.usuarios.update', $asistente->id), [
                'telefono' => '555-9999',
            ]);

        $respuesta->assertSessionHasErrors(['nombre', 'apellido', 'email', 'rol']);

        $this->assertSame(
            'asistente',
            DB::table('usuarios')->where('id', $asistente->id)->value('rol'),
            'Una edición parcial cambió el rol del usuario: la elevación de privilegios de TD-16 ha vuelto.'
        );
    }

    /**
     * FR3.2 y NFR3 — caso de error: un correo ya registrado se rechaza.
     *
     * Sin validación de servidor la petición llegaba al índice único de la
     * columna y reventaba con un error de base de datos en vez de con un
     * mensaje para el administrador.
     */
    public function test_un_correo_duplicado_se_rechaza(): void
    {
        $admin     = $this->crearUsuario('admin');
        $existente = $this->crearUsuario('cliente');

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.usuarios.create'))
            ->post(route('admin.usuarios.store'), $this->payloadFormulario([
                'email' => $existente->email,
            ]));

        $respuesta->assertSessionHasErrors('email');
        $respuesta->assertRedirect(route('admin.usuarios.create'));

        $this->assertSame(
            1,
            DB::table('usuarios')->where('email', $existente->email)->count(),
            'El alta creó un segundo usuario con un correo que ya estaba registrado.'
        );
    }

    /**
     * FR3.6 — el alta sin contraseña genera una aleatoria, no la fija.
     *
     * La aserción que discrimina es la primera: contra el código con el defecto
     * el hash guardado es el de `password123` para todos. La segunda fija la
     * promesa que hace la vista ("se genera una contraseña aleatoria"): dos
     * altas no comparten credencial.
     */
    public function test_el_alta_sin_contrasena_genera_una_aleatoria(): void
    {
        $admin = $this->crearUsuario('admin');

        $correoUno = 'sin.clave.uno.' . Str::random(6) . '@example.test';
        $correoDos = 'sin.clave.dos.' . Str::random(6) . '@example.test';

        foreach ([$correoUno, $correoDos] as $correo) {
            $this->actingAs($admin)
                ->from(route('admin.usuarios.create'))
                ->post(route('admin.usuarios.store'), $this->payloadFormulario([
                    'email'    => $correo,
                    'password' => '', // el administrador deja el campo vacío
                ]))
                ->assertSessionHasNoErrors();
        }

        $hashUno = DB::table('usuarios')->where('email', $correoUno)->value('password_hash');
        $hashDos = DB::table('usuarios')->where('email', $correoDos)->value('password_hash');

        $this->assertNotNull($hashUno, 'El alta sin contraseña no llegó a crear el usuario.');
        $this->assertNotNull($hashDos, 'El alta sin contraseña no llegó a crear el segundo usuario.');

        $this->assertFalse(
            Hash::check('password123', $hashUno),
            'El alta volvió a guardar la contraseña fija "password123" de TD-16.'
        );

        $this->assertNotSame(
            $hashUno,
            $hashDos,
            'Dos altas sin contraseña comparten credencial.'
        );
    }

    /**
     * FR4.1, FR4.6 y NFR2 — regresión de la pérdida de datos clínicos de TD-16.
     *
     * Antes, desactivar un usuario significaba borrar su fila, y
     * `fk_pacientes_usuario ... ON DELETE CASCADE` se llevaba por delante la
     * ficha del paciente. La baja lógica no puede tocar nada de eso.
     */
    public function test_la_baja_logica_conserva_el_expediente(): void
    {
        $admin   = $this->crearUsuario('admin');
        $cliente = $this->crearUsuario('cliente');
        $fichas  = $this->crearHistorialClinico($cliente->id);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.usuarios.index'))
            ->patch(route('admin.usuarios.toggle', $cliente->id));

        $respuesta->assertRedirect(route('admin.usuarios.index'));
        $respuesta->assertSessionHas('success');

        $this->assertSame(
            0,
            (int) DB::table('usuarios')->where('id', $cliente->id)->value('activo'),
            'La baja lógica no marcó al usuario como inactivo.'
        );

        $this->assertDatabaseHas('usuarios', ['id' => $cliente->id]);

        $this->assertSame(
            1,
            DB::table('pacientes')->where('id', $fichas['paciente'])->count(),
            'Desactivar al usuario borró su ficha de paciente: el borrado en cascada de TD-16 ha vuelto.'
        );

        $this->assertSame(
            1,
            DB::table('citas')->where('id', $fichas['cita'])->count(),
            'Desactivar al usuario borró su cita.'
        );

        $this->assertSame(
            1,
            DB::table('expedientes')->where('id', $fichas['expediente'])->count(),
            'Desactivar al usuario borró su expediente clínico.'
        );
    }

    /**
     * FR4.5 — la baja es reversible: un segundo paso devuelve `activo` a 1.
     */
    public function test_la_reactivacion_funciona(): void
    {
        $admin   = $this->crearUsuario('admin');
        $cliente = $this->crearUsuario('cliente', ['activo' => 1]);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.index'))
            ->patch(route('admin.usuarios.toggle', $cliente->id))
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame(
            0,
            (int) DB::table('usuarios')->where('id', $cliente->id)->value('activo'),
            'El primer paso debía desactivar al usuario.'
        );

        $this->actingAs($admin)
            ->from(route('admin.usuarios.index'))
            ->patch(route('admin.usuarios.toggle', $cliente->id))
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame(
            1,
            (int) DB::table('usuarios')->where('id', $cliente->id)->value('activo'),
            'El segundo paso debía reactivar al usuario y no lo hizo.'
        );
    }
}
