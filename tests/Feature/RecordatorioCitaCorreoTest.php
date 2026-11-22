<?php

namespace Tests\Feature;

use App\Mail\RecordatorioCita;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\Feature\Concerns\TokensAcceso;
use Tests\TestCase;

/**
 * Spec 052: el correo del recordatorio (CA1, CA9, CA10; TM2, TM3, TM7; P9), según `design.md` (`c2`).
 *
 * El correo recibe solo lo que fija CA1: el nombre del paciente, la fecha y hora de la cita (hora de la
 * clínica), el nombre del dentista y el servicio si lo hay. La dirección y el teléfono salen de
 * `config('clinica.contacto')`. «Ahora» es el miércoles 25 de noviembre de 2026, 10:30 de la clínica.
 */
class RecordatorioCitaCorreoTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss, TokensAcceso;

    private const HOJA = 'views/correos/recordatorio.css';

    private const APP_URL = 'https://dentissa.test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => self::APP_URL]);
        $this->travelTo(CarbonImmutable::parse('2026-11-25 10:30:00', config('clinica.zona_horaria')));
    }

    private function correo(string $fechaHora = '2026-11-26 10:30:00', ?string $servicio = 'Limpieza dental', string $nombre = 'Pablo'): RecordatorioCita
    {
        return new RecordatorioCita(nombre: $nombre, fechaHora: $fechaHora, dentista: 'Diego Primero', servicio: $servicio);
    }

    /** @return array{0: string, 1: string} el HTML (con los estilos en línea) y la versión de texto */
    private function renderizar(RecordatorioCita $correo): array
    {
        $html = '';
        $texto = '';
        (function () use (&$html, &$texto) {
            [$html, $texto] = $this->renderForAssertions();
        })->call($correo);

        return [$html, $texto];
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function sinEspacios(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /** CA1 (y /analyze A1): los datos de la cita, con y sin servicio; el dentista sin título. */
    public function test_el_correo_lleva_los_datos_de_la_cita(): void
    {
        $contacto = config('clinica.contacto');

        foreach (['con servicio' => 'Limpieza dental', 'sin servicio' => null] as $caso => $servicio) {
            [$html] = $this->renderizar($this->correo(servicio: $servicio));
            $xpath = $this->xpath($html);
            $texto = $this->sinEspacios($xpath->document->textContent);

            $this->assertStringContainsString('Hola, Pablo', $texto, "{$caso}: el nombre del paciente.");
            $h1 = $xpath->query('//h1');
            $this->assertSame(1, $h1->length, "{$caso}: un solo h1, la fecha.");
            $this->assertSame('jueves 26 de noviembre de 2026', mb_strtolower($this->sinEspacios($h1->item(0)->textContent)), "{$caso}: el día de la cita.");
            $this->assertStringContainsString('10:30', $texto, "{$caso}: la hora.");
            $this->assertStringContainsString('Diego Primero', $texto, "{$caso}: el dentista.");
            $this->assertDoesNotMatchRegularExpression('/\bDra?\.\s/u', $texto, "{$caso}: el dentista sin título (A1).");
            $this->assertStringContainsString($contacto['direccion'], $texto, "{$caso}: la dirección.");
            $this->assertStringContainsString($contacto['telefono'], $texto, "{$caso}: el teléfono.");
            $this->assertStringContainsString('Ver mis citas', $texto, "{$caso}: la acción.");
            if ($servicio === null) {
                $this->assertStringNotContainsString('Servicio', $texto, 'Sin servicio, no hay fila «Servicio».');
            } else {
                $this->assertStringContainsString('Limpieza dental', $texto, 'El servicio.');
            }
        }
    }

    /** D7: el asunto y el saludo dicen «mañana» o «hoy» según el día de la clínica. */
    public function test_el_asunto_dice_hoy_o_manana(): void
    {
        $manana = $this->correo('2026-11-26 10:30:00');
        $this->assertSame('Tu cita es mañana, jueves 26 de noviembre, a las 10:30', $manana->envelope()->subject);
        $this->assertStringContainsString('Te esperamos mañana', $this->sinEspacios($this->renderizar($manana)[0]));

        $hoy = $this->correo('2026-11-25 18:00:00');
        $this->assertSame('Tu cita es hoy, miércoles 25 de noviembre, a las 18:00', $hoy->envelope()->subject);
        $this->assertStringContainsString('Te esperamos hoy', $this->sinEspacios($this->renderizar($hoy)[0]));
    }

    /** RNF de accesibilidad: una versión de texto con los mismos datos. */
    public function test_la_version_de_texto_lleva_los_mismos_datos(): void
    {
        [, $texto] = $this->renderizar($this->correo());
        $texto = $this->sinEspacios($texto);

        $this->assertStringNotContainsString('<', $texto, 'Sin etiquetas.');
        foreach (['Pablo', 'jueves 26 de noviembre de 2026', '10:30', 'Diego Primero', 'Limpieza dental', config('clinica.contacto.direccion'), config('clinica.contacto.telefono'), self::APP_URL.'/cliente/citas'] as $dato) {
            $this->assertStringContainsString($dato, $texto, "El texto lleva «{$dato}».");
        }
    }

    /** RNF de accesibilidad: el logo con su texto alternativo. */
    public function test_el_logo_lleva_texto_alternativo(): void
    {
        $logo = $this->xpath($this->renderizar($this->correo())[0])->query('//img');

        $this->assertSame(1, $logo->length);
        $this->assertSame('Melissa López N. · Odontología integral', $logo->item(0)->getAttribute('alt'));
    }

    /** CA9, TM2: un solo enlace, «Ver mis citas», a `/cliente/citas` sin parámetros. */
    public function test_un_solo_enlace_a_mis_citas_sin_parametros(): void
    {
        $enlaces = $this->xpath($this->renderizar($this->correo())[0])->query('//a');

        $this->assertSame(1, $enlaces->length, 'Un único enlace: el teléfono va como texto.');
        $this->assertSame('Ver mis citas', $this->sinEspacios($enlaces->item(0)->textContent));
        $this->assertSame(self::APP_URL.'/cliente/citas', $enlaces->item(0)->getAttribute('href'), 'Sin parámetros ni token.');
    }

    /** CA9, TM2: sin sesión, «Mis citas» lleva al inicio de sesión. */
    public function test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion(): void
    {
        $this->get('/cliente/citas')->assertRedirect(route('login'));
    }

    /** CA10, TM3: el correo que sale de la revisión no lleva las notas de la cita. */
    public function test_el_correo_no_lleva_notas_ni_expediente(): void
    {
        $this->crearEscenario();
        Mail::fake();
        config(['recordatorios.activo' => true]);
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-25 12:30');
        DB::table('citas')->where('id', $cita)->update(['notas_paciente' => 'NOTA-PACIENTE-052', 'notas_internas' => 'NOTA-INTERNA-052']);

        Artisan::call('dentissa:recordatorios');

        Mail::assertSent(RecordatorioCita::class, function (RecordatorioCita $correo) {
            [$html, $texto] = $this->renderizar($correo);
            foreach (['NOTA-PACIENTE-052', 'NOTA-INTERNA-052', 'Nota interna original'] as $nota) {
                $this->assertStringNotContainsString($nota, $html);
                $this->assertStringNotContainsString($nota, $texto);
            }

            return true;
        });
    }

    /** TM7: el nombre sale escapado. */
    public function test_el_nombre_del_paciente_sale_escapado(): void
    {
        [$html, $texto] = $this->renderizar($this->correo(nombre: '<b>Pablo</b>'));

        $this->assertStringNotContainsString('<b>Pablo', $html);
        $this->assertStringContainsString('&lt;b&gt;Pablo', $html);
        $this->assertStringNotContainsString('<b>Pablo', $texto);
    }

    /** P9: la hoja del recordatorio solo usa valores de tokens (como `correos/acceso.css`, 041 D9). */
    public function test_la_hoja_del_recordatorio_solo_usa_valores_de_tokens(): void
    {
        $this->assertFalse($this->esValorDeToken('15.5px'), 'Control: un tamaño que no es token.');
        $this->assertFalse($this->esValorDeToken('#123456'), 'Control: un color que no es token.');

        $this->assertFileExists(resource_path(self::HOJA));
        $reglas = $this->analizarCss(file_get_contents(resource_path(self::HOJA)));
        $revisadas = 0;
        foreach ($reglas as [$selector, $cuerpo]) {
            foreach ($this->declaracionesRevisadas($cuerpo) as [$propiedad, $valor]) {
                $revisadas++;
                $this->assertTrue($this->esValorDeToken($valor), "{$selector} { {$propiedad}: {$valor} } no es el valor de un token.");
            }
        }
        $this->assertGreaterThan(10, $revisadas, 'La hoja declara colores, tamaños y espacios.');
    }

    /** P9: las plantillas no llevan estilos en línea (la hoja se pasa a línea al enviar). */
    public function test_las_plantillas_no_llevan_estilos_en_linea(): void
    {
        foreach (['recordatorio-cita.blade.php', 'recordatorio-cita-texto.blade.php'] as $plantilla) {
            $ruta = resource_path('views/correos/'.$plantilla);
            $this->assertFileExists($ruta);
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style\b/i', file_get_contents($ruta), "{$plantilla} sin estilos en línea.");
        }
    }
}
