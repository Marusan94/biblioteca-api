<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando una regla de negocio impide la operación (Parte 5.1).
 * El controller la traduce a una respuesta JSON con el código HTTP dado.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $status = 422,
    ) {
        parent::__construct($message);
    }
}
