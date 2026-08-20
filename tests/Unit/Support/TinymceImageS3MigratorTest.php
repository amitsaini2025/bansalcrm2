<?php

namespace Tests\Unit\Support;

use App\Support\TinymceImageS3Migrator;
use Tests\TestCase;

class TinymceImageS3MigratorTest extends TestCase
{
    public function test_extracts_uuid_filenames_from_note_html(): void
    {
        $html = '<p>Test</p><p><img src="https://bansalcrm.com/storage/tinymce-images/1211171e-7479-40b0-82fb-646e61edb032.png"></p>';
        $names = (new TinymceImageS3Migrator)->extractFilenames($html);

        $this->assertSame(['1211171e-7479-40b0-82fb-646e61edb032.png'], $names);
    }

    public function test_rewrites_local_storage_url_to_s3_and_leaves_document_urls_alone(): void
    {
        $file = '1211171e-7479-40b0-82fb-646e61edb032.png';
        $s3 = 'https://bansalcrm.s3.ap-southeast-2.amazonaws.com/tinymce-images/'.$file;
        $html = '<p><img src="https://bansalcrm.com/storage/tinymce-images/'.$file.'"></p>'
            .'<p><img src="https://bansalcrm.s3.ap-southeast-2.amazonaws.com/DEMO2308/application_documents/other.png"></p>';

        $out = (new TinymceImageS3Migrator)->rewriteStorageUrlsToS3($html, $file, $s3);

        $this->assertStringContainsString($s3, $out);
        $this->assertStringNotContainsString('/storage/tinymce-images/'.$file, $out);
        $this->assertStringContainsString('DEMO2308/application_documents/other.png', $out);
    }

    public function test_rejects_unsafe_filenames(): void
    {
        $migrator = new TinymceImageS3Migrator;

        $this->assertFalse($migrator->isSafeFilename('../secret.png'));
        $this->assertFalse($migrator->isSafeFilename('tinymce-images/foo.png'));
        $this->assertTrue($migrator->isSafeFilename('1211171e-7479-40b0-82fb-646e61edb032.png'));
    }
}
