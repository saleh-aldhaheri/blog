<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class UpdatePostContentData extends Data
{
    public function __construct(
        #[In(['heading', 'text', 'media'])]
        public string $type,

        public ?string $value,

        public int $order,

        public ?PostContentMediaData $media,
    ) {}

    public static function rules(): array
    {
        return [
            'type' => ['required', 'in:heading,text,media'],
            'value' => ['nullable', 'string', 'required_if:type,heading,text'],
            'media' => ['nullable', 'array',  'required_if:type,media'],
        ];
    }
}
