<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class UpdateProfileData extends Data
{
    public function __construct(
        public ?string $name = null,

        public ?string $email = null,

        public ?UploadedFile $avatar = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:256'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
