<?php

namespace App\Enums;

enum BusinessExceptionsEnums
{
    case AUTH;

    public function status(): int
    {
        return match ($this) {
            self::AUTH => 401,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::AUTH => 'unauthenticated',
        };
    }
}
