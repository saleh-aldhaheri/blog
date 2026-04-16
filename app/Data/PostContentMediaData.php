<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class PostContentMediaData extends Data
{
    public function __construct(
        public ?int $id = null,

        public ?string $url = null,

        public ?UploadedFile $newMedia = null,
    ) {}

    public static function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'url'],
            'newMedia' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
