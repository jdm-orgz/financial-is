<?php

namespace Tests\Unit\Enums;

use App\Enums\FileUploadModule;
use Tests\TestCase;

class FileUploadModuleTest extends TestCase
{
    public function test_max_size_uses_specific_config_if_available()
    {
        config(['fileupload.modules.app_config.max_size' => 2048]);
        config(['fileupload.default_max_size' => 1024]);

        $this->assertEquals(2048, FileUploadModule::APP_CONFIG->maxSize());
    }

    public function test_max_size_uses_default_config_if_specific_is_not_available()
    {
        config(['fileupload.modules.app_config.max_size' => null]);
        config(['fileupload.default_max_size' => 1024]);

        $this->assertEquals(1024, FileUploadModule::APP_CONFIG->maxSize());
    }
}
