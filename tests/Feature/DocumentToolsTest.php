<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class DocumentToolsTest extends FeatureTestCase
{
    public function test_admin_can_view_document_tools_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('documents.tools.index'))
            ->assertOk()
            ->assertSee(__('documents.tools.title'))
            ->assertSee(__('documents.tools.merge'));
    }

    public function test_admin_can_create_excel_from_table_data(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension required');
        }

        $this->actingAsAdmin();

        $response = $this->post(route('documents.tools.create-excel'), [
            'sheet_name' => 'Test',
            'rows' => "Ad\tTutar\nKalem 1\t100\nKalem 2\t250",
        ]);

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }
}
