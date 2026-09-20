<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresiones de TD-8: la pérdida silenciosa del precio y del color de agenda
 * de un servicio.
 *
 * El controlador machacaba con `$data['precio'] = 0` y
 * `$data['color_agenda'] = '#F4C0D1'` todo lo que llegara del formulario, así
 * que cualquier edición —aunque solo tocara la duración— ponía el precio a
 * cero. El defecto solo es observable contra filas reales, de ahí que estas
 * pruebas corran sobre la base MariaDB `dentissa_test`.
 *
 * Prerrequisito: `bash database/testing/bootstrap-test-db.sh` con el entorno
 * Docker en marcha. Ver unit-test-instructions.md.
 *
 * Aislamiento: `DatabaseTransactions` envuelve cada prueba en una transacción y
 * la revierte al terminar. Nunca `RefreshDatabase`: ese ejecutaría las
 * migraciones de Laravel y dejaría la base sin las tablas del dominio, que
 * vienen del volcado.
 */
class ServiciosPrecioTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Un administrador con el que atravesar los middleware `auth` y
     * `role:admin`. App\Models\Usuario es el proveedor del guard `web`.
     */
    private function crearAdmin(): Usuario
    {
        return Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'De Pruebas',
            'email'         => 'admin.pruebas.' . Str::random(8) . '@example.test',
            'rol'           => 'admin',
            'telefono'      => '555-0000',
            'activo'        => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    /**
     * Inserta un servicio directamente en la base, sin pasar por el
     * controlador: el fixture no debe depender del código bajo prueba.
     */
    private function crearServicio(array $valores = []): string
    {
        $id = (string) Str::uuid();

        DB::table('servicios')->insert(array_merge([
            'id'           => $id,
            'nombre'       => 'Servicio de pruebas ' . Str::random(6),
            'descripcion'  => 'Fila creada por ServiciosPrecioTest.',
            'categoria'    => 'Pruebas',
            'duracion_min' => 45,
            'precio'       => 18000.00,
            'color_agenda' => '#ED93B1',
            'activo'       => 1,
        ], $valores, ['id' => $id]));

        return $id;
    }

    /**
     * El formulario de servicios corregido envía siempre estos campos, con el
     * precio y el color precargados con los valores actuales del servicio.
     */
    private function payloadFormulario(array $valores = []): array
    {
        return array_merge([
            'nombre'       => 'Colocación de brackets',
            'categoria'    => 'Ortodoncia',
            'duracion_min' => 90,
            'descripcion'  => 'Aparatología fija metálica completa',
            'precio'       => '18000.00',
            'color_agenda' => '#ED93B1',
            'activo'       => '1',
        ], $valores);
    }

    /**
     * FR2.4 y FR2.5 — regresión directa de TD-8.
     *
     * Se edita un servicio caro cambiando únicamente la duración, con el resto
     * del formulario tal como lo precarga la vista. El precio debe sobrevivir.
     * Contra el código con el defecto esta prueba falla: `$data['precio'] = 0`
     * ignoraba el valor enviado.
     */
    public function test_el_precio_sobrevive_a_una_edicion_parcial(): void
    {
        $admin = $this->crearAdmin();
        $id    = $this->crearServicio(['duracion_min' => 90, 'precio' => 18000.00]);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $id))
            ->put(route('admin.servicios.update', $id), $this->payloadFormulario([
                'duracion_min' => 120, // lo único que cambia el administrador
            ]));

        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertRedirect(route('admin.servicios.index'));

        $servicio = DB::table('servicios')->where('id', $id)->first();

        $this->assertSame(
            18000.00,
            (float) $servicio->precio,
            'Editar la duración puso el precio a 0: la pérdida silenciosa de TD-8 ha vuelto.'
        );

        $this->assertSame(
            120,
            (int) $servicio->duracion_min,
            'La edición debía cambiar la duración y no lo hizo.'
        );

        $this->assertSame(
            '#ED93B1',
            $servicio->color_agenda,
            'El color de agenda se sobreescribió con el valor por defecto en vez de conservarse.'
        );
    }

    /**
     * FR2.6 y FR2.7 — el alta persiste lo que el administrador escribió.
     *
     * Contra el código con el defecto esta prueba falla dos veces: el precio se
     * guardaba como 0 y el color siempre como '#F4C0D1'.
     */
    public function test_el_alta_guarda_el_precio_y_el_color_escritos(): void
    {
        $admin  = $this->crearAdmin();
        $nombre = 'Servicio nuevo ' . Str::random(6);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.servicios.create'))
            ->post(route('admin.servicios.store'), $this->payloadFormulario([
                'nombre'       => $nombre,
                'precio'       => '1234.56',
                'color_agenda' => '#ABCDEF',
            ]));

        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertRedirect(route('admin.servicios.index'));

        $servicio = DB::table('servicios')->where('nombre', $nombre)->first();

        $this->assertNotNull($servicio, 'El alta no llegó a crear el servicio.');

        $this->assertSame(
            1234.56,
            (float) $servicio->precio,
            'El alta guardó un precio distinto del escrito (TD-8 lo forzaba a 0).'
        );

        $this->assertSame(
            '#ABCDEF',
            $servicio->color_agenda,
            'El alta guardó un color distinto del escrito (TD-8 lo forzaba a #F4C0D1).'
        );
    }

    /**
     * FR2.6 y NFR3 — caso de error: un precio negativo no se guarda.
     *
     * La validación de servidor debe rechazarlo y dejar la fila intacta.
     * Contra el código con el defecto no había validación alguna de `precio`.
     */
    public function test_un_precio_negativo_se_rechaza_y_no_modifica_el_servicio(): void
    {
        $admin = $this->crearAdmin();
        $id    = $this->crearServicio(['precio' => 18000.00, 'duracion_min' => 90]);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $id))
            ->put(route('admin.servicios.update', $id), $this->payloadFormulario([
                'precio'       => '-50',
                'duracion_min' => 120,
            ]));

        $respuesta->assertSessionHasErrors('precio');
        $respuesta->assertRedirect(route('admin.servicios.edit', $id));

        $servicio = DB::table('servicios')->where('id', $id)->first();

        $this->assertSame(
            18000.00,
            (float) $servicio->precio,
            'Una petición rechazada por validación llegó a modificar el precio.'
        );

        $this->assertSame(
            90,
            (int) $servicio->duracion_min,
            'Una petición rechazada por validación llegó a modificar la duración.'
        );
    }

    /**
     * FR2.6 y NFR3 — segundo caso de error exigido por la guardarraíl de fase
     * (camino feliz más dos casos de error como mínimo): un color fuera del
     * formato #RRGGBB se rechaza en la validación, así que la columna
     * varchar(7) nunca llega a recibir un valor que no quepa.
     */
    public function test_un_color_de_agenda_invalido_se_rechaza(): void
    {
        $admin = $this->crearAdmin();
        $id    = $this->crearServicio(['color_agenda' => '#ED93B1']);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.servicios.edit', $id))
            ->put(route('admin.servicios.update', $id), $this->payloadFormulario([
                'color_agenda' => 'rosa palo',
            ]));

        $respuesta->assertSessionHasErrors('color_agenda');

        $this->assertSame(
            '#ED93B1',
            DB::table('servicios')->where('id', $id)->value('color_agenda'),
            'Una petición rechazada por validación llegó a modificar el color de agenda.'
        );
    }
}
