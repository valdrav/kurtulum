<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

class BrandingTest extends FeatureTestCase
{
    public function test_profile_avatar_upload_and_remove(): void
    {
        Storage::fake('public');

        $user = $this->actingAsAdmin();
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'tr',
            'theme' => 'light',
            'avatar' => $file,
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertNotNull(user_avatar_url($user));

        $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'tr',
            'theme' => 'light',
            'remove_avatar' => '1',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNull($user->avatar);
    }

    public function test_branding_settings_page_and_logo_upload(): void
    {
        Storage::fake('public');

        $this->actingAsAdmin();

        $this->get(route('settings.branding'))
            ->assertOk()
            ->assertSee(__('settings.branding'));

        $logo = UploadedFile::fake()->create('logo.png', 50, 'image/png');

        $this->put(route('settings.branding.update'), [
            'site_short_name' => 'Kurtulum',
            'site_tagline' => 'İhracat ve Lojistik',
            'site_theme_color' => '#1e40af',
            'logo' => $logo,
        ])->assertRedirect();

        $this->assertSame('Kurtulum', Setting::get('site_short_name'));
        $this->assertSame('#1e40af', Setting::get('site_theme_color'));
        $this->assertNotEmpty(Setting::get('site_logo'));
        Storage::disk('public')->assertExists(Setting::get('site_logo'));

        $this->get('/media/' . Setting::get('site_logo'))
            ->assertOk();

        $this->get(route('manifest'))
            ->assertOk()
            ->assertJsonFragment(['short_name' => 'Kurtulum'])
            ->assertJsonFragment(['theme_color' => '#1e40af']);
    }

    public function test_branding_requires_settings_edit_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('viewer');

        $this->actingAs($user)
            ->get(route('settings.branding'))
            ->assertOk();

        Storage::fake('public');

        $this->actingAs($user)
            ->put(route('settings.branding.update'), [
                'site_short_name' => 'Test',
                'site_theme_color' => '#6366f1',
            ])
            ->assertForbidden();
    }
}
