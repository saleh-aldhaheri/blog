<?php

namespace App\Exceptions;

use App\Enums\BusinessExceptionsEnums;
use Exception;

class BusinessException extends Exception
{
    private array $errors;

    public function __construct(
        public BusinessExceptionsEnums $type,
        ?string $messageKey = null,
        ?array $errors = [],
    ) {
        $messageKey = $messageKey ?? $type->message();
        $this->errors = $errors;

        parent::__construct(
            $messageKey,
            $type->status()
        );
    }

    public function type(): BusinessExceptionsEnums
    {
        return $this->type;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
