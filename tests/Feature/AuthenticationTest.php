<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_does_not_grant_clinical_access_even_when_requested(): void
    {
        $this->post('/register', [
            'name' => 'Cuenta ficticia', 'email' => 'synthetic@example.test',
            'password' => 'Synthetic-Only-Password-42', 'password_confirmation' => 'Synthetic-Only-Password-42',
            'is_staff' => true,
        ])->assertRedirect('/home');
        $this->assertAuthenticated();
        $this->assertFalse(User::firstOrFail()->is_staff);
        $this->get('/home')->assertSee('pendiente de autorización');
        $this->get('/lista')->assertForbidden();
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'Synthetic-Only-Password-42']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'Synthetic-Only-Password-42'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_staff_access_can_be_granted_and_revoked_only_through_trusted_console(): void
    {
        $user = User::factory()->create();
        $this->artisan('app:authorize-staff', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_staff);
        $this->actingAs($user->fresh())->get('/lista')->assertOk();
        $this->artisan('app:authorize-staff', ['email' => $user->email, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_staff);
        $this->actingAs($user->fresh())->get('/lista')->assertForbidden();
        $this->artisan('app:authorize-staff', ['email' => 'missing@example.test'])->assertFailed();
    }
}
