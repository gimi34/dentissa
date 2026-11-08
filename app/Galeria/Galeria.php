<?php

namespace App\Galeria;

use App\Auditoria\Auditoria;
use App\Models\GaleriaFoto;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Spec 039 (CA8, CA9, CA10, CA12, CA16; TM3, TM7, TM8; plan D5 y D13): las operaciones de la
 * galería. Cada una va en una transacción con su evento de auditoría en modo crítico: si el
 * registro falla, no se publica, mueve ni quita nada (`AuditoriaNoDisponible`, 503).
 *
 * Los archivos se nombran con el `id` de la fila (`GaleriaFoto::archivo()`), nunca con datos de
 * la petición. Al publicar se escriben dentro de la transacción y se borran si falla; al quitar
 * se borran después del *commit*: si un borrado falla, queda un archivo que nadie sirve (la ruta
 * exige la fila) y el aviso `galeria.archivo_no_borrado`.
 */
class Galeria
{
    public const LIMITE = 12;

    public const MENSAJE_LLENA = 'Ya hay 12 fotos. Quita una antes de subir otra.';

    public function __construct(
        private readonly ProcesadorFoto $procesador,
        private readonly Auditoria $auditoria,
    ) {}

    /**
     * Publica una foto al final. Con paciente, guarda quién declaró el consentimiento (cuenta,
     * nombre copiado y fecha).
     *
     * @throws FotoNoProcesable si GD no puede leerla (deja `galeria.procesado_fallido`)
     * @throws ValidationException si ya hay 12 fotos
     */
    public function publicar(UploadedFile $archivo, string $descripcion, bool $conPaciente, Usuario $quien): GaleriaFoto
    {
        try {
            $procesada = $this->procesador->procesar($archivo);
        } catch (FotoNoProcesable $fallo) {
            // Sin el nombre del archivo ni la descripción (P8); el actor ya está en el Context.
            Log::warning('galeria.procesado_fallido', [
                'tipo_detectado' => (string) $archivo->getMimeType(),
                'bytes' => (int) $archivo->getSize(),
            ]);

            throw $fallo;
        }

        $disco = Storage::disk('galeria');
        $escritos = [];
        try {
            return DB::transaction(function () use ($procesada, $descripcion, $conPaciente, $quien, $disco, &$escritos) {
                // Bloquea las filas: dos subidas a la vez no dejan 13 (D13).
                $posiciones = GaleriaFoto::query()->lockForUpdate()->pluck('posicion');
                if ($posiciones->count() >= self::LIMITE) {
                    throw ValidationException::withMessages(['foto' => self::MENSAJE_LLENA]);
                }

                $foto = GaleriaFoto::create([
                    'descripcion' => $descripcion,
                    'posicion' => ((int) $posiciones->max()) + 1,
                    'con_paciente' => $conPaciente,
                    'consentimiento_usuario_id' => $conPaciente ? $quien->id : null,
                    'consentimiento_nombre' => $conPaciente ? mb_substr(trim($quien->nombre.' '.$quien->apellido), 0, 201) : null,
                    'consentimiento_en' => $conPaciente ? now() : null,
                    'ancho' => $procesada->ancho,
                    'alto' => $procesada->alto,
                    'bytes' => strlen($procesada->grande),
                    'bytes_pequena' => strlen($procesada->pequena),
                ]);

                foreach ([1600 => $procesada->grande, 800 => $procesada->pequena] as $medida => $bytes) {
                    $nombre = $foto->archivo($medida);
                    if (! $disco->put($nombre, $bytes)) {
                        throw new RuntimeException('No se pudo guardar la foto en el disco galeria.');
                    }
                    $escritos[] = $nombre;
                }

                $this->registrar('galeria_foto_publicada', $foto);

                return $foto;
            });
        } catch (Throwable $fallo) {
            foreach ($escritos as $nombre) {
                $disco->delete($nombre);
            }

            throw $fallo;
        }
    }

    /**
     * Cambia la foto con su vecina de arriba (`-1`) o de abajo (`+1`). En un extremo no hace
     * nada ni deja evento. Devuelve si se movió.
     */
    public function mover(GaleriaFoto $foto, int $paso): bool
    {
        $paso = $paso < 0 ? -1 : 1;

        return DB::transaction(function () use ($foto, $paso) {
            $filas = GaleriaFoto::query()->lockForUpdate()->get()->keyBy('posicion');
            $actual = $filas->firstWhere('id', $foto->id);
            $vecina = $actual ? $filas->get($actual->posicion + $paso) : null;
            if (! $actual || ! $vecina) {
                return false;
            }

            // Intercambio bajo el índice único de `posicion`: la 0 queda libre (las posiciones
            // empiezan en 1) y sirve de paso intermedio.
            [$desde, $hasta] = [$actual->posicion, $vecina->posicion];
            $actual->update(['posicion' => 0]);
            $vecina->update(['posicion' => $desde]);
            $actual->update(['posicion' => $hasta]);

            $this->registrar('galeria_foto_movida', $actual, ['direccion' => $paso < 0 ? 'arriba' : 'abajo']);

            return true;
        });
    }

    /** Quita la foto: borra la fila, compacta las posiciones y, tras el *commit*, sus archivos. */
    public function quitar(GaleriaFoto $foto): void
    {
        $quitada = DB::transaction(function () use ($foto) {
            GaleriaFoto::query()->lockForUpdate()->pluck('id');
            $fila = GaleriaFoto::query()->find($foto->id);
            if (! $fila) {
                return null;
            }

            $fila->delete();
            // En orden ascendente, para no chocar con el índice único al bajar cada una.
            DB::table('galeria_fotos')->where('posicion', '>', $fila->posicion)->orderBy('posicion')->decrement('posicion');

            $this->registrar('galeria_foto_quitada', $fila);

            return $fila;
        });

        if (! $quitada) {
            return;
        }

        $disco = Storage::disk('galeria');
        foreach (GaleriaFoto::MEDIDAS as $medida) {
            if (! $disco->delete($quitada->archivo($medida))) {
                Log::warning('galeria.archivo_no_borrado', ['foto_id' => $quitada->id, 'ancho' => $medida]);
            }
        }
    }

    /** Evento de auditoría crítico, sin la descripción ni la imagen (CA16). */
    private function registrar(string $accion, GaleriaFoto $foto, array $extra = []): void
    {
        $this->auditoria->registrar(
            $accion,
            'exito',
            'galeria_foto',
            $foto->id,
            detalle: array_merge(['con_paciente' => (bool) $foto->con_paciente, 'posicion' => (int) $foto->posicion], $extra),
            critico: true,
        );
    }
}
