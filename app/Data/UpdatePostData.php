<?php

namespace App\Data;

use App\Enums\PostStatusEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class UpdatePostData extends Data
{
    public function __construct(
        #[Min(20), Max(255)]
        public ?string $title,

        public int $categoryId,

        #[DataCollectionOf(UpdatePostContentData::class)]
        public ?DataCollection $content,

        #[In(PostStatusEnum::PUBLISHED->value, PostStatusEnum::DRAFT->value)]
        public ?string $status = PostStatusEnum::DRAFT->value,

    ) {}

    public static function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'min:20', 'max:255'],
            'content' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
