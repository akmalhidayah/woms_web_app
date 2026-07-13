<?php

namespace Tests\Feature\Pkm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PkmNotificationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_relative_and_same_host_urls_are_allowed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($user)->post(route('pkm.notifications.read'), [
            'notification_key' => 'safe-relative', 'redirect_url' => '/pkm/lhpp',
        ])->assertRedirect('/pkm/lhpp');

        $this->actingAs($user)->post(route('pkm.notifications.read'), [
            'notification_key' => 'safe-absolute', 'redirect_url' => url('/pkm/dashboard'),
        ])->assertRedirect(url('/pkm/dashboard'));
    }

    public function test_unsafe_redirect_urls_fall_back_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PKM]);

        foreach (['javascript:alert(1)', 'data:text/html,test', 'ftp://localhost/file', '//evil.example.com', 'https://evil.example.com', 'pkm/lhpp'] as $index => $url) {
            $this->actingAs($user)->post(route('pkm.notifications.read'), [
                'notification_key' => 'unsafe-'.$index, 'redirect_url' => $url,
            ])->assertRedirect(route('pkm.dashboard'));
        }
    }
}
