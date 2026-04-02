<?php

declare(strict_types=1);

namespace Modules\Blog\Enums;

enum PostPublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
