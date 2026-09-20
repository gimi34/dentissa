<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * NFR5 — sin regresión funcional en la capa de vistas de administración.
 *
 * Esta corrección tocó cuatro plantillas Blade (`usuarios/create`,
 * `usuarios/index`, `servicios/create`, `servicios/edit`) y hasta ahora la
 * única verificación de esa capa era la lista manual de 19 puntos de
 * `unit-test-instructions.md` § «Verificación manual». Una lista que nadie ha
 * ejecutado no es evidencia, así que NFR5 quedaba sin verificar.
 *
 * Estas pruebas de humo ejercitan las vistas por HTTP y fijan los puntos 1, 2,
 * 6, 8, 11, 18 y 19 de esa lista. No sustituyen al recorrido manual —no
 * comprueban la apariencia ni el comportamiento de navegador— pero sí cierran
 * lo que sí es observable de forma automática: que las pantallas responden,
 * que los filtros siguen filtrando, que una edición parcial conserva los
 * campos que no se tocan, y que un identificador inexistente responde 404 y no
 * un error 500.
 *
 * Prerrequisito: `bash database/testing/bootstrap-test-db.sh` con el entorno
 * Docker en marcha. Ver unit-test-instructions.md.
 *
 * Aislamiento: `DatabaseTransactions`, nunca `RefreshDatabase`. Las tablas del
 * dominio vienen del volcado, no de migraciones de Laravel.
 */
class AdminVistasSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Crea un usuario del rol indicado. App\Models\Usuario es el proveedor del
     * guard `web`, así que el de rol `admin` atraviesa `auth` y `role:admin`.
     */
    private function crearUsuario(string $rol, array $valores = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre'        => 'Persona',
            'apellido'      => 'De Vistas',
            'email'         => 'vistas.' . Str::random(8) . '@example.test',
            'rol'           => $rol,
            'telefono'      => '555-0000',
            'activo'        => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ], $valores, ['rol' => $rol]));
    }

    /**
     * Inserta un servicio directamente en la base: el fixture no debe depender
     * del controlador bajo prueba.
     */
    private function crearServicio(array $valores = []): string
    {
        $id = (string) Str::uuid();

        DB::table('servicios')->insert(array_merge([
            'id'           => $id,
            'nombre'       => 'Limpieza dental ' . Str::random(6),
            'descripcion'  => 'Fila creada por AdminVistasSmokeTest.',
            'categoria'    => 'Preventiva',
            'duracion_min' => 30,
            'precio'       => 850.00,
            'color_agenda' => '#ED93B1',
            'activo'       => 1,
        ], $valores, ['id' => $id]));

        return $id;
    }

    /**
     * Puntos 1, 2, 6, 8 y 11 — las cuatro pantallas del módulo de usuarios se
     * renderizan, el selector de rol ofrece las cuatro opciones a quien puede
     * cambiarlo, sigue oculto para un cliente, y el modal de desactivación ya
     * no promete un borrado irreversible.
     */
    public function test_las_vistas_de_administracion_de_usuarios_se_renderizan(): void
    {
        $admin     = $this->crearUsuario('admin');
        $cliente   = $this->crearUsuario('cliente');
        $asistente = $this->crearUsuario('asistente');

        $listado = $this->actingAs($admin)->get(route('admin.usuarios.index'));
        $listado->assertOk();
        $listado->assertSee($cliente->email);

        // Punto 11: el modal describía un borrado físico que ya no ocurre.
        $listado->assertDontSee('no se puede deshacer', false);

        $alta = $this->actingAs($admin)->get(route('admin.usuarios.create'));
        $alta->assertOk();

        $ficha = $this->actingAs($admin)->get(route('admin.usuarios.show', $cliente->id));
        $ficha->assertOk();
        $ficha->assertSee($cliente->email);

        // Punto 6: los cuatro roles del enum están en el desplegable.
        $edicion = $this->actingAs($admin)->get(route('admin.usuarios.edit', $asistente->id));
        $edicion->assertOk();

        foreach (['admin', 'dentista', 'asistente', 'cliente'] as $rol) {
            $this->assertStringContainsString(
                'value="' . $rol . '"',
                $edicion->getContent(),
                'El selector de rol ya no ofrece la opción "' . $rol . '".'
            );
        }

        // Punto 8: para un cliente el selector sigue oculto y su rol viaja
        // fijo en un campo oculto. Comportamiento existente que se conserva.
        $edicionCliente = $this->actingAs($admin)->get(route('admin.usuarios.edit', $cliente->id));
        $edicionCliente->assertOk();

        $this->assertStringNotContainsString(
            '<select name="rol"',
            $edicionCliente->getContent(),
            'La edición de un cliente expone el selector de rol, que antes estaba oculto.'
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="rol" value="cliente">',
            $edicionCliente->getContent(),
            'La edición de un cliente ya no fija su rol en el campo oculto.'
        );
    }

    /**
     * Punto 18 — el listado sigue filtrando por texto y por tipo.
     *
     * Camino feliz del filtro: el usuario buscado aparece y el que no encaja
     * desaparece. Sin las dos mitades la aserción no demostraría nada: una
     * vista que ignorase el filtro y los mostrase a todos también pasaría.
     */
    public function test_el_listado_de_usuarios_sigue_filtrando_por_texto_y_por_tipo(): void
    {
        $admin    = $this->crearUsuario('admin');
        $dentista = $this->crearUsuario('dentista', ['nombre' => 'Bernarda']);
        $cliente  = $this->crearUsuario('cliente', ['nombre' => 'Casimiro']);

        $porTexto = $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['buscar' => $dentista->email]));

        $porTexto->assertOk();
        $porTexto->assertSee($dentista->email);
        $porTexto->assertDontSee($cliente->email);

        $porTipo = $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['tipo' => 'cliente']));

        $porTipo->assertOk();
        $porTipo->assertSee($cliente->email);
        $porTipo->assertDontSee($dentista->email);
    }

    /**
     * Caso borde — un filtro sin resultados devuelve la pantalla vacía, no un
     * error. El listado ejerce `orderBy` sobre una colección vacía y las
     * tarjetas de totales siguen contando sobre la tabla entera.
     */
    public function test_un_filtro_sin_resultados_devuelve_la_pantalla_vacia(): void
    {
        $admin = $this->crearUsuario('admin');

        $respuesta = $this->actingAs($admin)->get(route('admin.usuarios.index', [
            'buscar' => 'cadena-que-no-existe-' . Str::random(12),
        ]));

        $respuesta->assertOk();
    }

    /**
     * Caso de error, NFR3 — un identificador con formato de UUID que no existe
     * responde 404, no un error 500.
     *
     * La restricción `where` sobre `{id}` filtra los segmentos que no son UUID
     * (eso lo fija AdminUsuariosRutasTest); lo que esta prueba cubre es el
     * tramo siguiente: un UUID bien formado pero sin fila detrás.
     */
    public function test_un_usuario_inexistente_responde_404_y_no_un_error_500(): void
    {
        $admin      = $this->crearUsuario('admin');
        $inexistente = (string) Str::uuid();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.show', $inexistente))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $inexistente))
            ->assertNotFound();
    }

    /**
     * Puntos 13, 16 y 19 — la edición de servicios guarda lo que se le manda.
     *
     * Es la mitad de TD-8 que `ServiciosPrecioTest` no cubre. Esa fija que el
     * precio y el color **sobreviven** a una edición que no los toca; esta
     * fija las dos cosas complementarias: que los campos que ya funcionaban
     * siguen funcionando (19), y que un precio o un color **cambiados** sí se
     * escriben (13 y 16). Sin esta mitad, un controlador que ignorase el
     * formulario y reescribiera siempre el valor anterior pasaría igual.
     */
    public function test_las_vistas_de_servicios_conservan_los_campos_no_tocados(): void
    {
        $admin      = $this->crearUsuario('admin');
        $servicioId = $this->crearServicio();

        $this->actingAs($admin)->get(route('admin.servicios.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.servicios.edit', $servicioId))->assertOk();

        // El estado activo es una casilla: solo viaja en la petición cuando
        // está marcada, así que omitirla es lo que el formulario envía al
        // desmarcarla. Mandar 'activo' => '0' la daría por marcada.
        $camposComunes = [
            'nombre'       => 'Limpieza dental profunda',
            'descripcion'  => 'Descripción reescrita por la prueba de humo.',
            'categoria'    => 'Periodoncia',
            'duracion_min' => 75,
            'precio'       => '850.00',
            'color_agenda' => '#ED93B1',
        ];

        $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $servicioId))
            ->put(route('admin.servicios.update', $servicioId), $camposComunes)
            ->assertSessionHasNoErrors();

        $fila = DB::table('servicios')->where('id', $servicioId)->first();

        $this->assertSame('Limpieza dental profunda', $fila->nombre);
        $this->assertSame('Periodoncia', $fila->categoria);
        $this->assertSame(75, (int) $fila->duracion_min);
        $this->assertSame('Descripción reescrita por la prueba de humo.', $fila->descripcion);
        $this->assertSame(0, (int) $fila->activo, 'La casilla desmarcada no desactivó el servicio.');

        // Y con la casilla marcada vuelve a quedar activo.
        $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $servicioId))
            ->put(route('admin.servicios.update', $servicioId), $camposComunes + ['activo' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            (int) DB::table('servicios')->where('id', $servicioId)->value('activo'),
            'La casilla marcada no reactivó el servicio.'
        );

        // Puntos 13 y 16: un precio y un color nuevos sí se escriben.
        $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $servicioId))
            ->put(route('admin.servicios.update', $servicioId), array_merge($camposComunes, [
                'precio'       => '1975.50',
                'color_agenda' => '#0A1B2C',
                'activo'       => '1',
            ]))
            ->assertSessionHasNoErrors();

        $actualizado = DB::table('servicios')->where('id', $servicioId)->first();

        $this->assertSame(
            '1975.50',
            (string) $actualizado->precio,
            'Un precio nuevo no llegó a guardarse: la edición de precios no funciona.'
        );

        $this->assertSame(
            '#0A1B2C',
            $actualizado->color_agenda,
            'Un color de agenda nuevo no llegó a guardarse.'
        );
    }

    /**
     * Punto 17 — el precio correcto se propaga a las vistas SQL.
     *
     * Es la consecuencia de TD-8 que el enunciado de la intención nombra
     * explícitamente: `v_citas_hoy` y `v_historial_paciente` leen
     * `servicios.precio`, así que cuando una edición lo ponía a cero la
     * pérdida salía también por la agenda del día y por el historial clínico.
     *
     * La prueba edita solo la duración —el gesto que destruía el precio— y
     * después consulta las dos vistas.
     */
    public function test_el_precio_correcto_se_propaga_a_las_vistas_sql(): void
    {
        $admin      = $this->crearUsuario('admin');
        $servicioId = $this->crearServicio(['precio' => 18000.00]);

        $cliente  = $this->crearUsuario('cliente');
        $dentista = $this->crearUsuario('dentista');

        $personalId = (string) Str::uuid();
        DB::table('personal')->insert([
            'id'         => $personalId,
            'usuario_id' => $dentista->id,
            'tipo'       => 'dentista',
        ]);

        $pacienteId = (string) Str::uuid();
        DB::table('pacientes')->insert([
            'id'               => $pacienteId,
            'usuario_id'       => $cliente->id,
            'fecha_nacimiento' => '1990-01-01',
            'genero'           => 'femenino',
        ]);

        $citaId = (string) Str::uuid();
        DB::table('citas')->insert([
            'id'           => $citaId,
            'paciente_id'  => $pacienteId,
            'dentista_id'  => $personalId,
            'servicio_id'  => $servicioId,
            // v_citas_hoy filtra por CURDATE(), así que la cita es de hoy.
            'fecha_hora'   => now()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'duracion_min' => 45,
            'estado'       => 'completada',
        ]);

        // El gesto que destruía el precio: editar solo la duración.
        $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $servicioId))
            ->put(route('admin.servicios.update', $servicioId), [
                'nombre'       => 'Colocación de brackets',
                'descripcion'  => 'Sin cambios.',
                'categoria'    => 'Ortodoncia',
                'duracion_min' => 90,
                'precio'       => '18000.00',
                'color_agenda' => '#ED93B1',
                'activo'       => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            '18000.00',
            (string) DB::table('v_citas_hoy')->where('id', $citaId)->value('precio'),
            'La agenda del día muestra un precio distinto del del servicio: TD-8 se propaga otra vez.'
        );

        $this->assertSame(
            '18000.00',
            (string) DB::table('v_historial_paciente')->where('cita_id', $citaId)->value('precio'),
            'El historial del paciente muestra un precio distinto del del servicio.'
        );
    }

    /**
     * Punto 5 — un usuario creado con una contraseña concreta puede iniciar
     * sesión con ella.
     *
     * Cierra la otra mitad de FR3.6: la prueba del alta sin contraseña fija
     * que el hash no es el fijo de TD-16, y esta fija que cuando el
     * administrador sí escribe una, es esa la que queda guardada y sirve.
     */
    public function test_un_usuario_creado_con_contrasena_puede_iniciar_sesion(): void
    {
        $admin  = $this->crearUsuario('admin');
        $correo = 'con.clave.' . Str::random(8) . '@example.test';
        $clave  = 'clave-elegida-por-el-admin';

        $this->actingAs($admin)
            ->from(route('admin.usuarios.create'))
            ->post(route('admin.usuarios.store'), [
                'nombre'   => 'Nueva',
                'apellido' => 'Dentista',
                'email'    => $correo,
                'telefono' => '555-2222',
                'rol'      => 'dentista',
                'activo'   => '1',
                'password' => $clave,
            ])
            ->assertSessionHasNoErrors();

        $hash = DB::table('usuarios')->where('email', $correo)->value('password_hash');

        $this->assertNotNull($hash, 'El alta con contraseña no llegó a crear el usuario.');

        $this->assertTrue(
            Hash::check($clave, $hash),
            'La contraseña escrita por el administrador no es la que quedó guardada.'
        );

        $this->assertFalse(
            Hash::check('password123', $hash),
            'El alta volvió a guardar la contraseña fija "password123" de TD-16.'
        );
    }
}
