<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Botón de alta de la lista de pacientes por rol (ficha docs/changes/2026-11-08-fix-boton-alta-pacientes.md):
 * cada rol solo enlaza al alta de su propio grupo de rutas, y el dentista, que no tiene alta de
 * pacientes, no ve ningún enlace de alta (ni en la barra superior ni en el estado vacío).
 */
class PacientesBotonAltaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    /** Búsqueda que no coincide con ningún paciente: muestra el estado vacío. */
    private const SIN_RESULTADOS = 'zz-sin-coincidencias-zz';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_admin_y_asistente_enlazan_al_alta_de_su_propio_rol(): void
    {
        foreach (['admin' => $this->admin, 'asistente' => $this->asistente] as $rol => $usuario) {
            // Con datos, solo «Nuevo paciente»; vacía, además «Registrar primer paciente».
            foreach (['lista' => ['', 1], 'vacía' => ['?buscar='.self::SIN_RESULTADOS, 2]] as $caso => [$consulta, $esperados]) {
                $html = $this->actingAs($usuario)->get("/{$rol}/pacientes{$consulta}")->assertOk()->getContent();
                $altas = $this->enlacesDeAlta($html);

                $this->assertCount($esperados, $altas, "{$rol} ({$caso}): enlaces de alta esperados.");
                if ($esperados === 2) {
                    $this->assertStringContainsString('No se encontraron pacientes', $html, "{$rol}: el caso vacío muestra el estado vacío.");
                }
                foreach ($altas as $ruta) {
                    $this->assertSame("/{$rol}/pacientes/crear", $ruta, "{$rol} ({$caso}): el alta es la de su rol.");
                    $this->actingAs($usuario)->get($ruta)->assertOk();
                }
            }
        }
    }

    public function test_el_dentista_no_ve_enlaces_de_alta_de_pacientes(): void
    {
        foreach (['lista' => '', 'vacía' => '?buscar='.self::SIN_RESULTADOS] as $caso => $consulta) {
            $html = $this->actingAs($this->dentista1)->get("/dentista/pacientes{$consulta}")->assertOk()->getContent();

            $this->assertSame([], $this->enlacesDeAlta($html), "dentista ({$caso}): sin enlaces de alta.");
        }
        $this->assertStringContainsString('No se encontraron pacientes', $html, 'El caso vacío muestra el estado vacío.');
    }

    /** Rutas de los enlaces de la página que llevan a un alta de pacientes de cualquier rol. */
    private function enlacesDeAlta(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $rutas = [];
        foreach ((new DOMXPath($dom))->query('//a[@href]') as $enlace) {
            $ruta = parse_url($enlace->getAttribute('href'), PHP_URL_PATH) ?? '';
            if (preg_match('#/pacientes/crear$#', $ruta)) {
                $rutas[] = $ruta;
            }
        }

        return $rutas;
    }
}
