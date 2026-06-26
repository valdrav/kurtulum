<?php

namespace Tests\Unit;

use App\Services\EmailAttachmentService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EmailAttachmentServiceTest extends TestCase
{
    protected function isAttachment($structure): bool
    {
        $service = new EmailAttachmentService;
        $method = new ReflectionMethod(EmailAttachmentService::class, 'isAttachmentPart');
        $method->setAccessible(true);

        return $method->invoke($service, $structure);
    }

    protected function extractFilename($structure): ?string
    {
        $service = new EmailAttachmentService;
        $method = new ReflectionMethod(EmailAttachmentService::class, 'extractFilename');
        $method->setAccessible(true);

        return $method->invoke($service, $structure);
    }

    public function test_detects_attachment_disposition(): void
    {
        $part = (object) [
            'type' => 3,
            'subtype' => 'PDF',
            'disposition' => 'attachment',
            'dparameters' => [(object) ['attribute' => 'filename', 'value' => 'fatura.pdf']],
        ];

        $this->assertTrue($this->isAttachment($part));
    }

    public function test_detects_inline_pdf_with_filename_as_attachment(): void
    {
        $part = (object) [
            'type' => 3,
            'subtype' => 'PDF',
            'disposition' => 'inline',
            'dparameters' => [(object) ['attribute' => 'filename', 'value' => 'fatura.pdf']],
        ];

        $this->assertTrue($this->isAttachment($part));
    }

    public function test_skips_inline_embedded_image_with_content_id(): void
    {
        $part = (object) [
            'type' => 5,
            'subtype' => 'PNG',
            'disposition' => 'inline',
            'id' => '<logo@mail>',
            'dparameters' => [(object) ['attribute' => 'filename', 'value' => 'logo.png']],
        ];

        $this->assertFalse($this->isAttachment($part));
    }

    public function test_detects_octet_stream_without_disposition(): void
    {
        $part = (object) [
            'type' => 3,
            'subtype' => 'OCTET-STREAM',
            'parameters' => [(object) ['attribute' => 'name', 'value' => 'dosya.zip']],
        ];

        $this->assertTrue($this->isAttachment($part));
    }

    public function test_decodes_rfc2231_filename(): void
    {
        $part = (object) [
            'dparameters' => [(object) [
                'attribute' => 'filename*',
                'value' => "UTF-8''fatura%20%C3%96rnek.pdf",
            ]],
        ];

        $this->assertSame('fatura Örnek.pdf', $this->extractFilename($part));
    }
}
