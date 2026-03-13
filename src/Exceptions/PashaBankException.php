<?php

namespace Sarkhanrasimoghlu\PashaBank\Exceptions;

use RuntimeException;
use Throwable;

class PashaBankException extends RuntimeException
{
    protected array $context = [];

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $context = [])
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
