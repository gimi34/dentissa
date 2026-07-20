<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Servicio extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'servicios';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id', 'nombre', 'descripcion', 'precio', 'categoria', 
        'duracion_min', 'color_agenda', 'activo', 'creado_en'
    ];
}