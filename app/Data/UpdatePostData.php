<?php

namespace App\Data;

use App\Enums\PostStatusEnum;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class UpdatePostData extends Data
{
    public function __construct(
        #[Min(5), Max(255)]
        public string|Optional $title,

        public int $categoryId,

        #[DataCollectionOf(PostContentData::class)]
        public null|Optional|DataCollection $content,

        #[In(PostStatusEnum::PUBLISHED->value, PostStatusEnum::DRAFT->value)]
        public string|Optional $status,

        public ?UploadedFile $thumbnails = null,
    ) {}

    public static function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'min:5', 'max:255'],
            'content' => ['sometimes', 'nullable', 'array'],
            'thumbnails' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
