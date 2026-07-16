<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Servicio extends Model
{
    // Agregamos HasUuids para que Laravel sepa crear y leer los IDs largos
    use HasFactory, HasUuids;

    protected $table = 'servicios';

    // 1. Le decimos que el ID NO es numérico autoincrementable
    public $incrementing = false;
    
    // 2. Le indicamos que el tipo de dato del ID es texto (string)
    protected $keyType = 'string';

    // 3. Apagamos los timestamps automáticos porque tu tabla usa "creado_en"
    public $timestamps = false;

    // Los campos que permitimos llenar
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio'
    ];
}