<?php

namespace Database\State;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EnsureAdminSeeded
{
    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        User::create([
            'email' => 'saleh@gmail.com',
            'password' => Hash::make('password'),
            'name' => 'saleh',
            'role' => RoleEnum::ADMIN->value,
        ]);
    }

    private function present(): bool
    {
        return User::where('email', 'saleh@gmail.com')->exists();
    }
}
