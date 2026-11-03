<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Spec 030, CA2 (TM4, TM9, TM10): la aplicación detrás del túnel.
 *
 * En producción, nginx recibe la petición de `cloudflared` y la pasa a php-fpm
 * con `X-Forwarded-Proto` y con la IP real del cliente ya puesta en
 * `REMOTE_ADDR` (`real_ip` desde `CF-Connecting-IP`, plan D3). Laravel solo
 * confía en el protocolo:
 *
 *  - con `X-Forwarded-Proto: https`, las URL y las redirecciones salen en https;
 *  - la cookie de sesión lleva `Secure` cuando `SESSION_SECURE_COOKIE=true`;
 *  - un `X-Forwarded-For` falso no cambia la IP (el límite de intentos cuenta
 *    por `REMOTE_ADDR`);
 *  - un `X-Forwarded-Host` o un `X-Forwarded-Port` ajenos no cambian el host ni
 *    el puerto de las URL generadas (TM10).
 */
class ProxyHttpsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_prueba/url', fn () => url('/'));
        Route::middleware('web')->get('/_prueba/ip', fn () => request()->ip());
    }

    /**
     * Host y puerto con los que el test hace las peticiones (los de `APP_URL`
     * en `phpunit.xml`); en producción, el subdominio sin puerto.
     */
    private function host(): string
    {
        $url = parse_url(config('app.url'));

        return $url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
    }

    private function cookieDeSesion($respuesta): ?Cookie
    {
        foreach ($respuesta->headers->getCookies() as $cookie) {
            if ($cookie->getName() === config('session.cookie')) {
                return $cookie;
            }
        }

        return null;
    }

    public function test_detras_del_proxy_las_urls_salen_en_https(): void
    {
        $respuesta = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/_prueba/url');

        $this->assertSame('https://'.$this->host(), $respuesta->getContent());
    }

    /** Spec 038 (D10): con la página de inicio, `/` ya no redirige; la redirección se prueba con `/admin`. */
    public function test_detras_del_proxy_las_redirecciones_salen_en_https(): void
    {
        $respuesta = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/admin');

        $respuesta->assertRedirect('https://'.$this->host().'/login');
    }

    public function test_la_cookie_de_sesion_lleva_secure(): void
    {
        config(['session.secure' => true]);

        $respuesta = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/login');

        $cookie = $this->cookieDeSesion($respuesta);
        $this->assertNotNull($cookie, 'La respuesta no deja la cookie de sesión.');
        $this->assertTrue($cookie->isSecure(), 'La cookie de sesión no lleva Secure.');
    }

    public function test_un_x_forwarded_for_falso_no_cambia_la_ip(): void
    {
        $respuesta = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-For' => '6.6.6.6, 203.0.113.7',
            ])
            ->get('/_prueba/ip');

        $this->assertSame('203.0.113.7', $respuesta->getContent());
    }

    public function test_un_x_forwarded_host_ajeno_no_cambia_el_host(): void
    {
        $respuesta = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'atacante.example',
        ])->get('/_prueba/url');

        $this->assertStringNotContainsString('atacante.example', $respuesta->getContent());
        $this->assertStringEndsWith('://'.$this->host(), $respuesta->getContent());
    }

    public function test_un_x_forwarded_port_ajeno_no_cambia_el_puerto(): void
    {
        $respuesta = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => '9999',
        ])->get('/admin');

        $this->assertStringNotContainsString(':9999', (string) $respuesta->headers->get('Location'));
        $this->assertStringEndsWith('://'.$this->host().'/login', (string) $respuesta->headers->get('Location'));
    }
}
