<?php

namespace Tests\Feature\Gallery;

use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Lauthz\Facades\Enforcer;
use Tests\TestCase;

class GalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Enforcer::shouldReceive('enforce')->andReturn(true);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    // =========================================================
    // index
    // =========================================================

    public function test_gallery_index_returns_inertia_response(): void
    {
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('proofs/images');
        Storage::disk('public')->makeDirectory('proofs/videos');

        $response = $this->actingAs($this->user)->get(route('gallery.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Gallery/Index')
            ->has('groups')
            ->has('filters')
            ->has('total')
        );
    }

    public function test_gallery_index_lists_image_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/test_image.jpg', 'fake-image-content');

        $response = $this->actingAs($this->user)->get(route('gallery.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Gallery/Index')
            ->where('total', 1)
        );
    }

    public function test_gallery_index_lists_video_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/videos/test_video.mp4', 'fake-video-content');

        $response = $this->actingAs($this->user)->get(route('gallery.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Gallery/Index')
            ->where('total', 1)
        );
    }

    public function test_gallery_index_filter_by_image_type_excludes_videos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'img');
        Storage::disk('public')->put('proofs/videos/clip.mp4', 'vid');

        $response = $this->actingAs($this->user)->get(route('gallery.index', ['type' => 'image']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('total', 1)
            ->where('filters.type', 'image')
        );
    }

    public function test_gallery_index_filter_by_video_type_excludes_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'img');
        Storage::disk('public')->put('proofs/videos/clip.mp4', 'vid');

        $response = $this->actingAs($this->user)->get(route('gallery.index', ['type' => 'video']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('total', 1)
            ->where('filters.type', 'video')
        );
    }

    public function test_gallery_index_respects_sort_filter(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)->get(route('gallery.index', ['sort' => 'size_asc']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'size_asc')
        );
    }

    public function test_gallery_index_respects_group_by_year(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)->get(route('gallery.index', ['group_by' => 'year']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.group_by', 'year')
        );
    }

    public function test_gallery_index_requires_authentication(): void
    {
        $response = $this->get(route('gallery.index'));
        $response->assertRedirect(route('login'));
    }

    // =========================================================
    // download
    // =========================================================

    public function test_gallery_download_returns_zip_for_valid_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'image-content');

        $response = $this->actingAs($this->user)->post(route('gallery.download'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/zip', $response->headers->get('Content-Type') ?? '');
    }

    public function test_gallery_download_validates_required_paths(): void
    {
        $response = $this->actingAs($this->user)->post(route('gallery.download'), [
            'paths' => [],
        ]);

        $response->assertSessionHasErrors('paths');
    }

    public function test_gallery_download_returns_422_when_no_files_exist(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post(route('gallery.download'), [
            'paths' => ['proofs/images/nonexistent.jpg'],
        ]);

        $response->assertStatus(422);
    }

    // =========================================================
    // destroy
    // =========================================================

    public function test_gallery_destroy_deletes_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'image-content');

        $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        Storage::disk('public')->assertMissing('proofs/images/photo.jpg');
    }

    public function test_gallery_destroy_nullifies_proof_image_path_in_db(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'image-content');

        $realization = TransactionReplacementRealization::factory()->create([
            'proof_image_path' => 'proofs/images/photo.jpg',
        ]);

        $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        $this->assertNull($realization->fresh()->proof_image_path);
    }

    public function test_gallery_destroy_nullifies_proof_video_path_in_db(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/videos/clip.mp4', 'video-content');

        $realization = TransactionReplacementRealization::factory()->create([
            'proof_video_path' => 'proofs/videos/clip.mp4',
        ]);

        $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => ['proofs/videos/clip.mp4'],
        ]);

        $this->assertNull($realization->fresh()->proof_video_path);
    }

    public function test_gallery_destroy_validates_required_paths(): void
    {
        $response = $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => [],
        ]);

        $response->assertSessionHasErrors('paths');
    }

    public function test_gallery_destroy_redirects_back_on_success(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('proofs/images/photo.jpg', 'content');

        $response = $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => ['proofs/images/photo.jpg'],
        ]);

        $response->assertRedirect();
    }

    public function test_gallery_destroy_gracefully_handles_missing_file(): void
    {
        Storage::fake('public');

        // Should not throw; just skip missing files
        $response = $this->actingAs($this->user)->delete(route('gallery.destroy'), [
            'paths' => ['proofs/images/does_not_exist.jpg'],
        ]);

        $response->assertRedirect();
    }
}
