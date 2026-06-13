<?php

namespace Tests\Feature\Master;

use App\Domain\Settings\Models\Setting;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::factory()->create(['name' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_can_view_edit_app_config(): void
    {
        $response = $this->actingAs($this->user)->get('/app-config');
        $response->assertStatus(200);
    }

    public function test_can_update_app_name(): void
    {
        $response = $this->actingAs($this->user)->post('/app-config', [
            'app_name' => 'New App Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'New App Name',
        ]);
    }

    public function test_can_upload_app_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg');

        $response = $this->actingAs($this->user)->post('/app-config', [
            'app_name' => 'Test App',
            'app_logo' => $file,
        ]);

        $response->assertRedirect();
        
        $setting = Setting::where('key', 'app_logo')->first();
        $this->assertNotNull($setting);
        
        Storage::disk('public')->assertExists($setting->value);
    }

    public function test_can_replace_existing_app_logo(): void
    {
        Storage::fake('public');

        // Add existing logo
        $oldFile = UploadedFile::fake()->image('old_logo.jpg');
        $oldPath = $oldFile->storeAs('app-logo', 'old_logo.jpg', 'public');
        Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $oldPath]);

        $newFile = UploadedFile::fake()->image('new_logo.jpg');

        $response = $this->actingAs($this->user)->post('/app-config', [
            'app_name' => 'Test App',
            'app_logo' => $newFile,
        ]);

        $response->assertRedirect();
        
        $setting = Setting::where('key', 'app_logo')->first();
        $this->assertNotNull($setting);
        
        // Assert old logo is deleted
        Storage::disk('public')->assertMissing($oldPath);
        // Assert new logo exists
        Storage::disk('public')->assertExists($setting->value);
    }

    public function test_can_remove_app_logo(): void
    {
        Storage::fake('public');
        
        // Add existing logo
        $file = UploadedFile::fake()->image('logo.jpg');
        $path = $file->storeAs('app-logo', 'logo.jpg', 'public');
        Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);

        $response = $this->actingAs($this->user)->post('/app-config', [
            'app_name' => 'Test App',
            'remove_logo' => true,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('settings', [
            'key' => 'app_logo',
        ]);
        
        Storage::disk('public')->assertMissing($path);
    }
}
