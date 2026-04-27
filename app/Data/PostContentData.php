<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class PostContentData extends Data
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
            'order' => ['required', 'integer'],
            'value' => ['nullable', 'string', 'required_if:type,heading', 'required_if:type,text'],
            'media' => ['nullable', 'array', 'required_if:type,media'],
        ];
    }
}
