<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ChangePasswordData extends Data
{
    public function __construct(
        public string $current_password,

        public string $password,

        public string $password_confirmation,
    ) {}

    public static function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
