<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'google_id',
        'password_hash',
        'rol',
        'telefono',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // 🔑 IMPORTANTE: Laravel usa "password"
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
