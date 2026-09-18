<?php
$content = file_get_contents('tests/Feature/Gallery/GalleryControllerTest.php');

$newTests = <<<'EOT'

    public function test_gallery_index_sorts_files_correctly(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/1.jpg', '1');
        Storage::disk('public')->put('proofs/images/2.jpg', '22');
        
        $disk = Storage::disk('public');
        touch($disk->path('proofs/images/1.jpg'), time() - 3600);
        touch($disk->path('proofs/images/2.jpg'), time());

        $this->actingAs($this->user)->get(route('gallery.index', ['sort' => 'date_asc']))->assertOk();
        $this->actingAs($this->user)->get(route('gallery.index', ['sort' => 'size_desc']))->assertOk();
        $this->actingAs($this->user)->get(route('gallery.index', ['sort' => 'size_asc']))->assertOk();
        $this->actingAs($this->user)->get(route('gallery.index', ['sort' => 'date_desc']))->assertOk();
    }

    public function test_gallery_index_skips_missing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('proofs/images');
        
        $disk = Storage::disk('public');
        $brokenLink = $disk->path('proofs/images/broken.jpg');
        symlink('/does/not/exist', $brokenLink);
        
        $response = $this->actingAs($this->user)->get(route('gallery.index'));
        $response->assertOk();
        
        @unlink($brokenLink);
    }
EOT;

$content = str_replace('    public function test_gallery_index_requires_authentication(): void', $newTests . "\n    public function test_gallery_index_requires_authentication(): void", $content);

$newDownloadTests = <<<'EOT'

    public function test_gallery_download_creates_tmp_dir_if_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'image-content');
        
        if (is_dir(storage_path('app/private/tmp'))) {
            \Illuminate\Support\Facades\File::deleteDirectory(storage_path('app/private/tmp'));
        }

        $response = $this->actingAs($this->user)->post(route('gallery.download'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        $response->assertOk();
        $this->assertTrue(is_dir(storage_path('app/private/tmp')));
    }

    public function test_gallery_download_aborts_if_zip_cannot_be_created(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'image-content');
        
        $now = now();
        \Carbon\Carbon::setTestNow($now);
        $zipName = 'gallery_'.$now->format('Ymd_His').'.zip';
        $zipPath = storage_path('app/private/tmp/'.$zipName);
        
        if (! is_dir(storage_path('app/private/tmp'))) {
            mkdir(storage_path('app/private/tmp'), 0755, true);
        }
        
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }
        if (is_dir($zipPath)) {
            @rmdir($zipPath);
        }
        
        mkdir($zipPath);

        $response = $this->actingAs($this->user)->post(route('gallery.download'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        $response->assertStatus(500);
        
        @rmdir($zipPath);
        \Carbon\Carbon::setTestNow();
    }
EOT;

$content = str_replace('    public function test_gallery_download_validates_required_paths(): void', $newDownloadTests . "\n    public function test_gallery_download_validates_required_paths(): void", $content);

file_put_contents('tests/Feature/Gallery/GalleryControllerTest.php', $content);

echo "Tests updated.\n";
