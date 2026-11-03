<?php

namespace App\Models;

use App\Mail\EnlaceRestablecimiento;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

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

    protected function casts(): array
    {
        return [
            'version_sesion' => 'integer',
        ];
    }

    // 🔑 IMPORTANTE: Laravel usa "password"
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /** Spec 041 (CA2, D3): el broker llama aquí; el correo sale por el SMTP de Gmail de la 032. */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new EnlaceRestablecimiento($this, $token));
    }
}
