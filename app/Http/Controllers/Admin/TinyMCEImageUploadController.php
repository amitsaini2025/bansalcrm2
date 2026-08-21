<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TinymceImageS3Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TinyMCEImageUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Upload an image for TinyMCE (notes, email signatures, descriptions, etc.).
     * Stores on the S3 disk. TinyMCE receives a staff preview URL so the editor can show the image.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',
        ], [
            'file.required' => 'No image selected.',
            'file.image' => 'The file must be an image.',
            'file.mimetypes' => 'Allowed formats: JPEG, PNG, GIF, WebP.',
            'file.max' => 'Image must be under 2MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp'], true)) {
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'png',
            };
        }
        $name = Str::uuid().'.'.$extension;
        $path = 'tinymce-images/'.$name;

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        return response()->json([
            'location' => route('tinymce.preview-image', ['filename' => $name]),
        ]);
    }

    /**
     * Staff-only redirect to a short-lived S3 URL so TinyMCE can render the image.
     * Stored note HTML is still the canonical S3 object URL (stripped on editor save).
     */
    public function preview(string $filename)
    {
        $migrator = new TinymceImageS3Migrator;
        if (! $migrator->isSafeFilename($filename)) {
            abort(404);
        }

        try {
            $url = Storage::disk('s3')->temporaryUrl($migrator->s3Key($filename), now()->addMinutes(15));
        } catch (\Throwable) {
            abort(404);
        }

        return redirect()->away($url);
    }
}
