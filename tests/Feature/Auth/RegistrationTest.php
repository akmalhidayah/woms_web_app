<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = Volt::test('auth.register')
            ->set('name', 'Test User')
            ->set('email', ' TEST@EXAMPLE.COM ')
            ->set('role', User::ROLE_APPROVER)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('user.dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(User::ROLE_APPROVER, auth()->user()->role);
        $this->assertSame('test@example.com', auth()->user()->email);
    }
}
