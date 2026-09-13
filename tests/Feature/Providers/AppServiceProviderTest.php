<?php

namespace Tests\Feature\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        parent::tearDown();
    }

    public function test_forces_https_when_forwarded_proto_is_https()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertStringStartsWith('https://', url('/'));
    }

    public function test_forces_https_in_production_environment()
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertStringStartsWith('https://', url('/'));
    }

    public function test_forces_https_when_host_contains_ngrok()
    {
        $this->app['request']->headers->set('HOST', 'random-ngrok.ngrok-free.app');

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertStringStartsWith('https://', url('/'));
    }
}
