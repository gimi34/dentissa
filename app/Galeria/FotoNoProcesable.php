<?php

namespace App\Galeria;

use RuntimeException;

/**
 * Spec 039 (CA12): GD no pudo leer la foto subida. El mensaje es para el registro interno; quien
 * sube ve «No se pudo leer la foto. Prueba con otro archivo.».
 */
class FotoNoProcesable extends RuntimeException {}
