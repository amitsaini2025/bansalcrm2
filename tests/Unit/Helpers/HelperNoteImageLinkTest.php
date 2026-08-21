<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelperNoteImageLinkTest extends TestCase
{
    public function test_note_display_replaces_https_image_with_view_link(): void
    {
        $html = '<p>Test5</p><p><img src="https://example.com/tinymce-images/abc.png" alt=""></p>';
        $out = Helper::normalizeActivityDescriptionHtml($html, true);

        $this->assertStringContainsString('View image', $out);
        $this->assertStringContainsString('https://example.com/tinymce-images/abc.png', $out);
        $this->assertStringContainsString('target="_blank"', $out);
        $this->assertStringNotContainsString('<img', strtolower($out));
    }

    public function test_default_normalize_keeps_images_for_non_note_display(): void
    {
        $html = '<p><img src="https://example.com/a.png" alt=""></p>';
        $out = Helper::normalizeActivityDescriptionHtml($html);

        $this->assertStringContainsString('<img', strtolower($out));
        $this->assertStringNotContainsString('View image', $out);
    }

    public function test_javascript_image_src_is_not_turned_into_a_link(): void
    {
        $html = '<p><img src="javascript:alert(1)"></p>';
        $out = Helper::normalizeActivityDescriptionHtml($html, true);

        $this->assertStringNotContainsString('javascript:', strtolower($out));
        $this->assertStringNotContainsString('<img', strtolower($out));
        $this->assertStringNotContainsString('View image', $out);
    }

    public function test_note_display_keeps_local_storage_and_document_urls_unsigned(): void
    {
        $file = '1211171e-7479-40b0-82fb-646e61edb032.png';
        $local = 'https://bansalcrm.com/storage/tinymce-images/'.$file;
        $document = 'https://bansalcrm.s3.ap-southeast-2.amazonaws.com/DEMO2308/application_documents/other.png';
        $html = '<p><img src="'.$local.'"></p><p><img src="'.$document.'"></p>';

        $out = Helper::normalizeActivityDescriptionHtml($html, true);

        $this->assertStringContainsString($local, $out);
        $this->assertStringContainsString($document, $out);
        $this->assertStringContainsString('View image', $out);
        $this->assertStringNotContainsString('<img', strtolower($out));
    }

    public function test_note_display_signs_s3_tinymce_image_url_at_view_time(): void
    {
        $file = '1211171e-7479-40b0-82fb-646e61edb032.png';
        $src = 'https://bansalcrm.s3.ap-southeast-2.amazonaws.com/tinymce-images/'.$file;
        $signed = 'https://signed.example/tinymce-images/'.$file.'?X-Amz-Signature=test';

        $disk = \Mockery::mock();
        $disk->shouldReceive('temporaryUrl')
            ->once()
            ->withArgs(function (string $key) use ($file) {
                return $key === 'tinymce-images/'.$file;
            })
            ->andReturn($signed);
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

        $out = Helper::normalizeActivityDescriptionHtml('<p><img src="'.$src.'"></p>', true);

        $this->assertStringContainsString('View image', $out);
        $this->assertStringContainsString($signed, $out);
        $this->assertStringNotContainsString($src, $out);
    }
}
