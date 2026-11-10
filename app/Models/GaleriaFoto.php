<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Spec 039: una foto de la galería de trabajos. Las operaciones que la crean, la mueven o la
 * quitan viven en `App\Galeria\Galeria` (con transacción y auditoría); este modelo solo describe
 * la fila. El archivo de cada medida se nombra con el `id` (`archivo()`), nunca con datos de la
 * petición (ADR 0009).
 */
class GaleriaFoto extends Model
{
    use HasUuids;

    /** Medidas que se guardan y se sirven de cada foto (plan → Entrega de las fotos). */
    public const MEDIDAS = [800, 1600];

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'galeria_fotos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'descripcion', 'posicion', 'con_paciente', 'consentimiento_usuario_id',
        'consentimiento_nombre', 'consentimiento_en', 'ancho', 'alto', 'bytes', 'bytes_pequena',
    ];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'con_paciente' => 'boolean',
            'consentimiento_en' => 'datetime',
            'ancho' => 'integer',
            'alto' => 'integer',
            'bytes' => 'integer',
            'bytes_pequena' => 'integer',
        ];
    }

    /** Las fotos en el orden en que se publican (1…N). */
    public function scopeOrdenadas(Builder $consulta): Builder
    {
        return $consulta->orderBy('posicion');
    }

    /** La cuenta que declaró el consentimiento; el nombre copiado se conserva aunque se borre. */
    public function consentimientoUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'consentimiento_usuario_id');
    }

    /** Nombre del archivo de una medida en el disco `galeria`. */
    public function archivo(int $medida): string
    {
        return "{$this->id}-{$medida}.webp";
    }

    /** Ancho del WebP pequeño: el grande si mide menos de 800 px. */
    public function anchoPequena(): int
    {
        return min(800, $this->ancho);
    }
}
