<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Servicio extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'servicios';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id', 'nombre', 'descripcion', 'precio', 'categoria',
        'duracion_min', 'color_agenda', 'activo', 'creado_en',
    ];

    /** Grupo de los servicios sin categoría en las páginas públicas (spec 038). */
    public const OTROS = 'Otros servicios';

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', 1);
    }

    /**
     * Catálogo público (spec 038, CA4 y CA6; TM2): los servicios activos agrupados por
     * categoría, con solo su nombre y su descripción, en una sola consulta.
     *
     * Las categorías van en orden alfabético y se agrupan sin distinguir mayúsculas ni
     * acentos, con la primera grafía que aparece (/analyze A13); los servicios sin
     * categoría y una categoría llamada «Otros servicios» van juntos al final.
     *
     * @return Collection<string, Collection<int, object{nombre: string, descripcion: ?string}>>
     */
    public static function porCategoria(): Collection
    {
        $clave = fn (string $texto) => Str::lower(Str::ascii(trim($texto)));
        $otros = $clave(self::OTROS);
        $titulos = [];
        $grupos = [];

        foreach (static::query()->activos()->orderBy('nombre')->get(['nombre', 'descripcion', 'categoria']) as $servicio) {
            $categoria = trim((string) $servicio->categoria);
            $grupo = $categoria === '' ? $otros : $clave($categoria);
            $titulos[$grupo] ??= $grupo === $otros ? self::OTROS : $categoria;
            $grupos[$grupo][] = (object) ['nombre' => $servicio->nombre, 'descripcion' => $servicio->descripcion];
        }

        uksort($grupos, fn ($a, $b) => [$a === $otros, $a] <=> [$b === $otros, $b]);

        return collect($grupos)->mapWithKeys(fn ($servicios, $grupo) => [$titulos[$grupo] => collect($servicios)]);
    }
}
