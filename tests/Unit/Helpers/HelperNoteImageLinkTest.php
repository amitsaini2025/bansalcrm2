<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
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
}
