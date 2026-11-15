<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoBootstrapTest extends TestCase
{
    use RefreshDatabase;

    private function configureDemo(): void
    {
        config([
            'demo.enabled' => true,
            'demo.email' => 'demo@example.test',
            'demo.password' => 'Synthetic-demo-password-42',
        ]);
    }

    public function test_demo_bootstrap_is_disabled_by_default(): void
    {
        $this->artisan('app:bootstrap-demo')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_bootstrap_requires_credentials(): void
    {
        config(['demo.enabled' => true]);
        $this->artisan('app:bootstrap-demo')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->configureDemo();
        config(['demo.email' => 'invalid']);
        $this->artisan('app:bootstrap-demo')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_short_password_is_rejected(): void
    {
        $this->configureDemo();
        config(['demo.password' => 'short']);
        $this->artisan('app:bootstrap-demo')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_private_variables_create_staff_account_with_a_hashed_password(): void
    {
        $this->configureDemo();
        $this->artisan('app:bootstrap-demo')->assertSuccessful();
        $user = User::firstOrFail();
        $this->assertTrue($user->is_staff);
        $this->assertTrue(Hash::check(config('demo.password'), $user->password));
        $this->assertNotSame(config('demo.password'), $user->password);
        $this->post('/login', ['email' => config('demo.email'), 'password' => config('demo.password')])->assertRedirect('/home');
        $this->get('/lista')->assertOk();
    }

    public function test_bootstrap_does_not_escalate_an_existing_unapproved_account(): void
    {
        $this->configureDemo();
        $user = User::factory()->create(['email' => config('demo.email')]);
        $password = $user->password;
        $this->artisan('app:bootstrap-demo')->assertFailed();
        $this->assertFalse($user->fresh()->is_staff);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_restarting_does_not_duplicate_staff_or_overwrite_the_password(): void
    {
        $this->configureDemo();
        $this->artisan('app:bootstrap-demo')->assertSuccessful();
        $user = User::firstOrFail();
        $password = $user->password;
        config(['demo.password' => 'Another-synthetic-password-42']);
        $this->artisan('app:bootstrap-demo')->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $user->fresh()->password);
    }
}
