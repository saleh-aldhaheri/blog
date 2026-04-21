<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class PostContentData extends Data
{
    public function __construct(
        #[In(['heading', 'text', 'media'])]
        public string $type,

        public int $order,

        public ?string $value,

        public ?UploadedFile $file,
    ) {}

    public static function rules(): array
    {
        return [
            'type' => ['required', 'in:heading,text,media'],
            'value' => ['nullable', 'string', 'required_if:type,heading,text'],
            'file' => ['nullable', 'file', 'image', 'required_if:type,media'],
        ];
    }
}
