<?php

namespace App\Enums;

enum BusinessExceptionsEnums
{
    case AUTH;
    case INVALID;

    public function status(): int
    {
        return match ($this) {
            self::AUTH => 401,
            self::INVALID => 422,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::AUTH => 'unauthenticated',
            self::INVALID => 'invalid_request',
        };
    }
}
