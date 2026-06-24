<?php

namespace Tests\Feature;

use App\Models\SystemModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\FeatureTestCase;

class ModuleInstallTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        $target = base_path('modules/DemoUpload');
        if (File::isDirectory($target)) {
            File::deleteDirectory($target);
        }

        SystemModule::where('slug', 'demo-upload')->delete();

        parent::tearDown();
    }

    public function test_modules_page_lists_discovered_modules(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('settings.modules.index'));

        $response->assertOk();
        $response->assertSee(__('extensions.modules'));
        $response->assertSee('Insurance Tracker');
        $response->assertSee('Depo & Stok');
    }

    public function test_admin_can_upload_module_zip(): void
    {
        $this->actingAsAdmin();

        $zip = $this->fixtureZip();

        $response = $this->post(route('settings.modules.upload'), [
            'module_file' => $zip,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDirectoryExists(base_path('modules/DemoUpload'));
        $this->assertDatabaseHas('system_modules', [
            'slug' => 'demo-upload',
            'name' => 'Demo Upload Modulu',
        ]);
    }

    public function test_admin_can_remove_uploaded_module(): void
    {
        $this->actingAsAdmin();

        $this->post(route('settings.modules.upload'), ['module_file' => $this->fixtureZip()]);

        $module = SystemModule::where('slug', 'demo-upload')->first();
        $this->assertNotNull($module);

        $response = $this->delete(route('settings.modules.destroy', $module));

        $response->assertRedirect();
        $this->assertDatabaseMissing('system_modules', ['slug' => 'demo-upload']);
        $this->assertDirectoryDoesNotExist(base_path('modules/DemoUpload'));
    }

    protected function fixtureZip(): UploadedFile
    {
        $path = base_path('tests/fixtures/modules/demo-upload.zip');
        $this->assertFileExists($path);

        return new UploadedFile($path, 'demo-upload.zip', 'application/zip', null, true);
    }
}
