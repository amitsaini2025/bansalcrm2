<?php

namespace Tests\Unit\Support;

use App\Support\TinymceImageS3Migrator;
use Illuminate\Support\Facades\Storage;
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

    public function test_key_from_url_is_s3_tinymce_only_not_local_or_documents(): void
    {
        $migrator = new TinymceImageS3Migrator;
        $file = '1211171e-7479-40b0-82fb-646e61edb032.png';

        $this->assertSame(
            'tinymce-images/'.$file,
            $migrator->keyFromUrl('https://bansalcrm.s3.ap-southeast-2.amazonaws.com/tinymce-images/'.$file)
        );
        $this->assertNull($migrator->keyFromUrl('https://bansalcrm.com/storage/tinymce-images/'.$file));
        $this->assertSame(
            'tinymce-images/'.$file,
            $migrator->keyFromUrl('https://bansalcrm.com/tinymce/image/'.$file)
        );
        $this->assertNull($migrator->keyFromUrl('https://bansalcrm.s3.ap-southeast-2.amazonaws.com/DEMO2308/application_documents/other.png'));
    }

    public function test_temporary_display_url_falls_back_when_s3_cannot_sign(): void
    {
        $file = '1211171e-7479-40b0-82fb-646e61edb032.png';
        $src = 'https://bansalcrm.s3.ap-southeast-2.amazonaws.com/tinymce-images/'.$file;

        $disk = \Mockery::mock();
        $disk->shouldReceive('temporaryUrl')->andThrow(new \RuntimeException('cannot sign'));
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

        $this->assertSame($src, (new TinymceImageS3Migrator)->temporaryDisplayUrl($src));
    }

    public function test_data_uri_images_are_uploaded_to_s3_and_src_rewritten(): void
    {
        Storage::fake('s3');

        $png = base64_encode('fake-png-bytes');
        $html = '<p>Test4</p><p><img src="data:image/png;base64,'.$png.'"></p>';
        $out = (new TinymceImageS3Migrator)->replaceDataUrisWithS3($html);

        $this->assertStringNotContainsString('data:image', $out);
        $this->assertStringContainsString('tinymce-images/', $out);
        $this->assertCount(1, Storage::disk('s3')->allFiles('tinymce-images'));
    }

    public function test_data_uri_rewrite_leaves_document_and_http_images_alone(): void
    {
        Storage::fake('s3');

        $html = '<p><img src="https://bansalcrm.s3.ap-southeast-2.amazonaws.com/DEMO2308/application_documents/other.png"></p>';
        $out = (new TinymceImageS3Migrator)->replaceDataUrisWithS3($html);

        $this->assertStringContainsString('DEMO2308/application_documents/other.png', $out);
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }
}
