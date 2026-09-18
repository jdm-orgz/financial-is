<?php
$content = file_get_contents('tests/Feature/Gallery/GalleryControllerTest.php');

$oldTest = <<<'EOT'
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

$newTest = <<<'EOT'
    public function test_gallery_index_skips_missing_files(): void
    {
        $mockDisk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $mockDisk->shouldReceive('exists')->with('proofs/images')->andReturn(true);
        $mockDisk->shouldReceive('exists')->with('proofs/transfers')->andReturn(false);
        $mockDisk->shouldReceive('exists')->with('proofs/videos')->andReturn(false);
        
        $mockDisk->shouldReceive('files')->with('proofs/images')->andReturn(['proofs/images/fake.jpg']);
        $mockDisk->shouldReceive('path')->with('proofs/images/fake.jpg')->andReturn('/invalid/path/fake.jpg');
        
        Storage::shouldReceive('disk')->with('public')->andReturn($mockDisk);
        
        $response = $this->actingAs($this->user)->get(route('gallery.index'));
        $response->assertOk();
    }
EOT;

$content = str_replace($oldTest, $newTest, $content);
file_put_contents('tests/Feature/Gallery/GalleryControllerTest.php', $content);
