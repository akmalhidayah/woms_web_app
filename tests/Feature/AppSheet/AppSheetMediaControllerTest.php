<?php

namespace Tests\Feature\AppSheet;

use App\Models\User;
use App\Services\AppSheet\GoogleDriveMediaService;
use App\Support\AppSheet\GoogleDriveMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AppSheetMediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_proxied_image_with_private_cache_headers(): void
    {
        $key = str_repeat('a', 64);
        $media = Mockery::mock(GoogleDriveMediaService::class);
        $media->shouldReceive('media')->once()->with($key)->andReturn(
            new GoogleDriveMedia('image-bytes', 'image/png'),
        );
        $this->app->instance(GoogleDriveMediaService::class, $media);

        $this->actingAs($this->admin())
            ->get(route('admin.appsheet.media.show', ['key' => $key]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'max-age=3600, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertContent('image-bytes');
    }

    public function test_media_endpoint_requires_admin_access_and_an_internal_key(): void
    {
        $key = str_repeat('b', 64);

        $this->get(route('admin.appsheet.media.show', ['key' => $key]))
            ->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user)
            ->get(route('admin.appsheet.media.show', ['key' => $key]))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/appsheet/media/arbitrary-drive-file-id')
            ->assertNotFound();
    }

    public function test_missing_or_unavailable_drive_image_returns_not_found_without_sensitive_details(): void
    {
        $key = str_repeat('c', 64);
        $media = Mockery::mock(GoogleDriveMediaService::class);
        $media->shouldReceive('media')->once()->with($key)->andReturnNull();
        $this->app->instance(GoogleDriveMediaService::class, $media);

        $this->actingAs($this->admin())
            ->get(route('admin.appsheet.media.show', ['key' => $key]))
            ->assertNotFound()
            ->assertDontSee('Google Drive');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
