<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class PostContentMediaData extends Data
{
    public function __construct(
        public int $id,
        public string $url,
        public ?UploadedFile $newMedia = null,
    ) {}
}
