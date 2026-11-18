<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ficha docs/changes/2026-11-17-fix-detalles-servicios-usuarios.md: detalles menores de las
 * pantallas de Servicios (spec 009) y Usuarios (spec 010) que anotaron sus «Observaciones».
 */
class DetallesServiciosUsuariosTest extends TestCase
{
    use DatabaseTransactions;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->crearUsuario('admin');
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona', 'apellido' => 'Detalles', 'email' => 'detalles.'.Str::lower(Str::random(10)).'@example.test',
            'rol' => $rol, 'activo' => 1, 'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    private function crearServicio(array $valores = []): string
    {
        $id = (string) Str::uuid();
        DB::table('servicios')->insert(array_merge([
            'nombre' => 'Servicio '.Str::random(8), 'descripcion' => 'Fila de DetallesServiciosUsuariosTest.',
            'categoria' => 'Preventiva', 'duracion_min' => 30, 'precio' => 500, 'color_agenda' => '#ED93B1', 'activo' => 1,
        ], $valores, ['id' => $id]));

        return $id;
    }

    private function formularioServicio(array $valores = []): array
    {
        return array_merge([
            'nombre' => 'Servicio '.Str::random(8), 'categoria' => 'Preventiva', 'duracion_min' => 30,
            'descripcion' => 'Alta desde DetallesServiciosUsuariosTest.', 'precio' => 500, 'color_agenda' => '#ED93B1',
        ], $valores);
    }

    /** Número que muestra la tarjeta de estadísticas con esa etiqueta. */
    private function tarjeta(string $html, string $etiqueta): ?int
    {
        $patron = '~class="stat-value">\s*(\d+)\s*</div>\s*<div class="stat-label">\s*'.preg_quote($etiqueta, '~').'\s*</div>~u';

        return preg_match($patron, $html, $m) ? (int) $m[1] : null;
    }

    // ── 1. Buscador de servicios ───────────────────────────────────────

    public function test_el_buscador_de_servicios_filtra_por_nombre_descripcion_y_categoria(): void
    {
        $marca = Str::lower(Str::random(10));
        $porNombre = 'Blanqueamiento '.$marca;
        $porDescripcion = 'Servicio '.Str::random(8);
        $porCategoria = 'Servicio '.Str::random(8);
        $otro = 'Servicio '.Str::random(8);
        $this->crearServicio(['nombre' => $porNombre]);
        $this->crearServicio(['nombre' => $porDescripcion, 'descripcion' => 'Incluye '.$marca.' y revisión.']);
        $this->crearServicio(['nombre' => $porCategoria, 'categoria' => 'Categoría '.$marca]);
        $this->crearServicio(['nombre' => $otro]);

        $respuesta = $this->actingAs($this->admin)->get(route('admin.servicios.index', ['buscar' => strtoupper($marca)]))
            ->assertOk()->assertSee($porNombre)->assertSee($porDescripcion)->assertSee($porCategoria)->assertDontSee($otro);

        $html = $respuesta->getContent();
        $this->assertSame(DB::table('servicios')->count(), $this->tarjeta($html, 'Servicios registrados'), 'El total no cambia con la búsqueda.');
        $this->assertSame(3, $this->tarjeta($html, 'Resultados encontrados'));
    }

    public function test_una_busqueda_sin_resultados_lo_dice_y_ofrece_limpiarla(): void
    {
        $this->actingAs($this->admin)->get(route('admin.servicios.index', ['buscar' => 'zzz-'.Str::random(12)]))
            ->assertOk()
            ->assertSee('Ningún servicio coincide con la búsqueda.')
            ->assertSee('href="'.route('admin.servicios.index').'"', false)
            ->assertSee('Ver todos los servicios')
            ->assertDontSee('Registrar primer servicio');
    }

    public function test_sin_busqueda_lista_todos_los_servicios(): void
    {
        $nombre = 'Servicio '.Str::random(8);
        $this->crearServicio(['nombre' => $nombre, 'activo' => 0]);

        $html = $this->actingAs($this->admin)->get(route('admin.servicios.index'))->assertOk()->assertSee($nombre)->getContent();

        $total = DB::table('servicios')->count();
        $this->assertSame($total, $this->tarjeta($html, 'Servicios registrados'));
        $this->assertSame($total, $this->tarjeta($html, 'Resultados encontrados'));
    }

    // ── 2. Ruta sin pantalla ───────────────────────────────────────────

    public function test_la_ficha_de_un_servicio_no_existe_y_responde_404(): void
    {
        $id = $this->crearServicio();

        $this->actingAs($this->admin)->get('/admin/servicios/'.$id)->assertNotFound();
    }

    // ── 3. Avisos de servicios ─────────────────────────────────────────

    public function test_alta_edicion_y_borrado_de_un_servicio_avisan(): void
    {
        $nombre = 'Servicio '.Str::random(8);
        $this->actingAs($this->admin)->post(route('admin.servicios.store'), $this->formularioServicio(['nombre' => $nombre]))
            ->assertRedirect(route('admin.servicios.index'))->assertSessionHas('success', 'Servicio registrado correctamente.');
        $id = DB::table('servicios')->where('nombre', $nombre)->value('id');

        $this->actingAs($this->admin)->put(route('admin.servicios.update', $id), $this->formularioServicio(['nombre' => $nombre, 'activo' => 1]))
            ->assertRedirect(route('admin.servicios.index'))->assertSessionHas('success', 'Servicio actualizado correctamente.');

        $this->actingAs($this->admin)->delete(route('admin.servicios.destroy', $id))
            ->assertRedirect(route('admin.servicios.index'))->assertSessionHas('success', 'Servicio eliminado.');

        $this->actingAs($this->admin)->get(route('admin.servicios.index'))->assertOk();
    }

    // ── 4. Resumen por rol de usuarios ─────────────────────────────────

    public function test_el_resumen_de_usuarios_cuenta_tambien_a_los_asistentes(): void
    {
        $this->crearUsuario('asistente');

        $html = $this->actingAs($this->admin)->get(route('admin.usuarios.index'))->assertOk()->getContent();

        foreach (['Administradores' => 'admin', 'Dentistas' => 'dentista', 'Asistentes' => 'asistente', 'Pacientes / Clientes' => 'cliente'] as $etiqueta => $rol) {
            $this->assertSame(Usuario::where('rol', $rol)->count(), $this->tarjeta($html, $etiqueta), "Tarjeta «{$etiqueta}».");
        }
    }

    /** Review ligera (menor 1): los asistentes también en el filtro por rol y con su grupo titulado. */
    public function test_el_listado_de_usuarios_filtra_y_agrupa_a_los_asistentes(): void
    {
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($this->admin)->get(route('admin.usuarios.index', ['tipo' => 'asistente']))
            ->assertOk()
            ->assertSee('<option value="asistente" selected>Asistentes</option>', false)
            ->assertSee($asistente->email)
            ->assertSee('font-size:16px;">Asistentes</span>', false)
            ->assertDontSee($this->admin->email);
    }

    /** Review ligera (menor 3): un filtro sin resultados no dice que no hay usuarios ni enlaza a «#». */
    public function test_un_filtro_de_usuarios_sin_resultados_lo_dice_y_ofrece_ver_todos(): void
    {
        $this->actingAs($this->admin)->get(route('admin.usuarios.index', ['buscar' => 'zzz-'.Str::random(12)]))
            ->assertOk()
            ->assertSee('Ningún usuario coincide con el filtro.')
            ->assertSee('Ver todos los usuarios')
            ->assertDontSee('No hay usuarios registrados aún')
            ->assertDontSee('href="#"', false);
    }

    // ── 5. Avisos de usuarios ──────────────────────────────────────────

    public function test_alta_y_edicion_de_un_usuario_avisan(): void
    {
        $correo = 'alta.'.Str::lower(Str::random(10)).'@example.test';
        $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Nueva', 'apellido' => 'Cuenta', 'email' => $correo, 'rol' => 'cliente', 'activo' => '1',
        ])->assertRedirect(route('admin.usuarios.index'))->assertSessionHas('success', 'Usuario registrado correctamente.');
        $id = DB::table('usuarios')->where('email', $correo)->value('id');

        $this->actingAs($this->admin)->put(route('admin.usuarios.update', $id), [
            'nombre' => 'Nueva', 'apellido' => 'Cuenta Editada', 'email' => $correo, 'rol' => 'cliente', 'activo' => '1',
        ])->assertRedirect(route('admin.usuarios.index'))->assertSessionHas('success', 'Datos del usuario actualizados.');
    }
}
