<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

enum FileUploadModule: string
{
    use EnumOptions;

    case APP_CONFIG = 'app_config';

    public function maxSize(): int
    {
        $specificSize = config("fileupload.modules.{$this->value}.max_size");
        return $specificSize ?? config('fileupload.default_max_size');
    }
}
