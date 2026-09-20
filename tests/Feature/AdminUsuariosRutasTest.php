<?php

namespace Tests\Feature;

use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Regresiones de la tabla de rutas del módulo de administración de usuarios.
 *
 * Estas pruebas interrogan al enrutador de Laravel directamente: no abren
 * sesión, no ejecutan middleware y no tocan la base de datos, así que corren
 * con la configuración de `phpunit.xml` tal como está.
 */
class AdminUsuariosRutasTest extends TestCase
{
    /**
     * Identificador con forma de UUID, como los de la columna usuarios.id
     * (char(36)). No necesita corresponder a ninguna fila real.
     */
    private const UUID_DE_EJEMPLO = '3800c172-24e3-11f1-8967-0045e2e7d554';

    /**
     * Devuelve la acción (Clase@método) a la que el enrutador resuelve una URL.
     */
    private function accionDe(string $uri, string $metodo = 'GET'): string
    {
        return Route::getRoutes()
            ->match(Request::create($uri, $metodo))
            ->getActionName();
    }

    /** FR1.1 — regresión directa de TD-11. */
    public function test_el_alta_de_usuarios_resuelve_al_metodo_create(): void
    {
        $this->assertSame(
            UsuarioController::class . '@create',
            $this->accionDe('/admin/usuarios/crear'),
            'El segmento literal "crear" volvió a quedar capturado por la ruta /admin/usuarios/{id}.'
        );
    }

    /**
     * FR1.3 y FR1.4 — estado correcto del listado tras retirar el duplicado.
     *
     * Aviso sobre el alcance de esta prueba: NO falla contra el código con el
     * fallo, y se ha comprobado ejecutándola contra él. El closure muerto y el
     * controlador compartían método y URI; Laravel indexa las rutas por
     * método+URI y descarta la anterior, y después del arranque reconstruye la
     * lista de nombres a partir de ese índice, así que el closure ya no era
     * alcanzable ni por URL ni por nombre. TD-12 era, en la práctica, código
     * muerto y confuso más que una ruta que se impusiera. Lo que esta prueba
     * fija es el estado correcto: el listado lo atiende el controlador, el
     * nombre del marcador de posición no existe y la ruta nombrada que sí usan
     * las vistas sigue en pie.
     */
    public function test_el_listado_de_usuarios_resuelve_al_controlador(): void
    {
        $this->assertSame(
            UsuarioController::class . '@index',
            $this->accionDe('/admin/usuarios'),
            '/admin/usuarios debe atenderla UsuarioController@index, no el closure del marcador de posición.'
        );

        $this->assertFalse(
            Route::has('admin.usuarios'),
            'El closure del marcador de posición sigue registrado bajo el nombre admin.usuarios.'
        );

        $this->assertTrue(
            Route::has('admin.usuarios.index'),
            'El listado debe seguir accesible por su ruta nombrada admin.usuarios.index.'
        );
    }

    /** FR1.2 — la restricción sobre {id} impide que un literal caiga en @show. */
    public function test_un_identificador_sin_forma_de_uuid_no_resuelve_a_show(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->accionDe('/admin/usuarios/no-soy-un-uuid');
    }

    /** FR4.2 y FR4.5 — el borrado físico desaparece y la baja lógica ocupa su lugar. */
    public function test_el_borrado_ya_no_existe_y_la_alternancia_si(): void
    {
        $this->assertFalse(
            Route::has('admin.usuarios.destroy'),
            'La ruta de borrado físico sigue registrada: desactivar un usuario podría destruir su expediente.'
        );

        $this->assertTrue(
            Route::has('admin.usuarios.toggle'),
            'Falta la ruta de baja lógica admin.usuarios.toggle.'
        );

        $this->assertSame(
            UsuarioController::class . '@toggleActivo',
            $this->accionDe('/admin/usuarios/' . self::UUID_DE_EJEMPLO . '/toggle', 'PATCH'),
            'La ruta de alternancia debe apuntar a UsuarioController@toggleActivo.'
        );
    }
}
