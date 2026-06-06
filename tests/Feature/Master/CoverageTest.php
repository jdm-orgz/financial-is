<?php

namespace Tests\Feature\Master;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\Outlet\Repositories\EloquentLinkedOutletUserRepository;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Domain\UserAccess\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class CoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_users_relation(): void
    {
        $role = Role::factory()->create(['name' => 'test_role']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($role->users->contains($user));
    }

    public function test_user_repository_find_by_email(): void
    {
        $role = Role::factory()->create(['name' => 'test_role']);
        $user = User::factory()->create(['role_id' => $role->id, 'email' => 'test@example.com']);

        $repository = new EloquentUserRepository;
        $found = $repository->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_production_password_defaults(): void
    {
        // Force the app to think it's in production
        $originalEnv = app()->environment();
        app()['env'] = 'production';

        $defaults = Password::defaults();

        // Let's resolve the callback by doing some validation
        $validator = Validator::make(['password' => 'secret'], [
            'password' => $defaults,
        ]);

        $this->assertTrue($validator->fails());

        app()['env'] = $originalEnv;
    }

    public function test_fortify_rate_limiters(): void
    {
        // Hit the two-factor limiter
        $request = Request::create('/two-factor-challenge', 'POST');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('login.id', 1);

        $limiter = RateLimiter::limiter('two-factor');
        $limit = $limiter($request);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(1, $limit->key);

        // Hit the passkeys limiter
        $request = Request::create('/login', 'POST', ['credential' => ['id' => 'test-id']]);
        $request->setLaravelSession(app('session')->driver());
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $limiter = RateLimiter::limiter('passkeys');
        $limit = $limiter($request);
        $this->assertEquals(10, $limit->maxAttempts);
        $this->assertEquals('test-id|127.0.0.1', $limit->key);
    }

    public function test_linked_outlet_user_repository_crud(): void
    {
        $repository = new EloquentLinkedOutletUserRepository;
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create();

        $link = $repository->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'is_active' => '1',
        ]);

        $this->assertNotNull($link);

        $found = $repository->findById($link->id);
        $this->assertNotNull($found);

        $repository->update($link->id, ['is_active' => '0']);
        $this->assertEquals('0', $repository->findById($link->id)->is_active);

        $repository->delete($link->id);
        $this->assertNull($repository->findById($link->id));

        $this->assertFalse($repository->update('invalid-id', []));
        $this->assertFalse($repository->delete('invalid-id'));

        $repository->getPaginated(10, 'search', 'user.name', 'asc', ['admin']);
    }

    public function test_user_model_has_permission(): void
    {
        $user = new User;
        $this->assertFalse($user->hasPermissionTo('master/*', '*'));
    }
}
