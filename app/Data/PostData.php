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

class PostData extends Data
{
    public function __construct(
        #[Min(20), Max(255)]
        public string $title,

        public UploadedFile $thumbnails,

        public int $categoryId,

        #[DataCollectionOf(PostContentData::class)]
        public DataCollection $content,

        #[In(PostStatusEnum::PUBLISHED->value, PostStatusEnum::DRAFT->value)]
        public ?string $status = PostStatusEnum::DRAFT->value,
    ) {}

    public static function rules(): array
    {
        return [
            'content.*.type' => ['required', 'in:heading,text,media'],
            'content.*.order' => ['required', 'integer'],
            'content.*.value' => [
                'nullable',
                'string',
                'required_if:content.*.type,heading',
                'required_if:content.*.type,text',
            ],
            'content.*.file' => [
                'nullable',
                'file',
                'image',
                'required_if:content.*.type,media',
            ],
        ];
    }
}
