<?php

namespace App\Enums;

enum PostStatusEnum: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';

    public static function toArray()
    {
        return [
            'published',
            'draft',
        ];
    }
}
