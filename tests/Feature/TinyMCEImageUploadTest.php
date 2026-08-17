<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TinyMCEImageUploadTest extends TestCase
{
    private function actingAsStaff(): Staff
    {
        $staff = new Staff([
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'secret',
        ]);
        $staff->id = 1;

        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_upload_tinymce_image(): void
    {
        Storage::fake('s3');

        $response = $this->postJson(route('tinymce.upload-image'), [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $response->assertUnauthorized();
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_authenticated_staff_uploads_image_to_s3_not_local_public_disk(): void
    {
        Storage::fake('s3');
        Storage::fake('public');

        $this->actingAsStaff();

        $response = $this->postJson(route('tinymce.upload-image'), [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $files = Storage::disk('s3')->allFiles('tinymce-images');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.png', $files[0]);

        $response->assertOk()
            ->assertJson([
                'location' => Storage::disk('s3')->url($files[0]),
            ]);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('s3');

        $this->actingAsStaff();

        $response = $this->postJson(route('tinymce.upload-image'), [
            'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }
}
