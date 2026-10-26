<?php

namespace App\Avisos;

use RuntimeException;

/**
 * Spec 032 (plan D8, Contratos): estado del vigilante entre dos ejecuciones,
 * en `storage/app/avisos/estado.json`.
 *
 * Solo guarda desplazamientos, contadores por minuto, momentos y huellas: nunca
 * una petición ni nada de quien la hace (TM14). Se escribe de forma atómica
 * (temporal + rename) para que una ejecución cortada no lo deje a medias.
 */
class Estado
{
    /**
     * @param  array{
     *     acceso: array{inodo: int|null, desplazamiento: int, malas_seguidas: int},
     *     minutos: array<int, array{peticiones: int, errores: int, malas: int}>,
     *     seguridad: array{desplazamientos: array<string, int>, fallos: list<array{0: int, 1: string|null, 2: string|null}>},
     *     avisos: array<string, array{ultimo_envio: int|null, pendiente: bool, acumulado: int, desde: int|null}>
     * }  $datos
     */
    private function __construct(private readonly string $ruta, public array $datos) {}

    /** Estado guardado, o null en la primera ejecución. */
    public static function cargar(string $ruta): ?self
    {
        if (! is_file($ruta)) {
            return null;
        }

        $datos = json_decode((string) file_get_contents($ruta), true);

        return is_array($datos) ? new self($ruta, $datos + self::vacio()) : null;
    }

    public static function nuevo(string $ruta): self
    {
        return new self($ruta, self::vacio());
    }

    public function guardar(): void
    {
        $carpeta = dirname($this->ruta);
        if (! is_dir($carpeta) && ! mkdir($carpeta, 0700, true) && ! is_dir($carpeta)) {
            throw new RuntimeException("No se pudo crear {$carpeta}");
        }

        $temporal = $this->ruta.'.'.getmypid().'.tmp';
        file_put_contents($temporal, json_encode($this->datos, JSON_THROW_ON_ERROR));
        rename($temporal, $this->ruta);
    }

    /** Estado de un tipo de aviso. */
    public function aviso(string $tipo): array
    {
        return ($this->datos['avisos'][$tipo] ?? []) + ['ultimo_envio' => null, 'pendiente' => false, 'acumulado' => 0, 'desde' => null];
    }

    public function guardarAviso(string $tipo, array $aviso): void
    {
        $this->datos['avisos'][$tipo] = $aviso;
    }

    private static function vacio(): array
    {
        return [
            'acceso' => ['inodo' => null, 'desplazamiento' => 0, 'malas_seguidas' => 0],
            'minutos' => [],
            'seguridad' => ['desplazamientos' => [], 'fallos' => []],
            'avisos' => [],
        ];
    }
}
