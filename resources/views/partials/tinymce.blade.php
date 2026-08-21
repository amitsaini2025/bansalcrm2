{{-- TinyMCE via Vite (Phase 2e). Include via @push('tinymce-scripts') on pages that need the editor. --}}
<script>
    window.TINYMCE_UPLOAD_URL = "{{ url(route('tinymce.upload-image')) }}";
    window.TINYMCE_PREVIEW_BASE = @json(url('/tinymce/image'));
    window.TINYMCE_S3_IMAGE_PREFIX = @json(preg_replace('/\/_x\.png$/i', '', \App\Helpers\Helper::s3ObjectUrl('tinymce-images/_x.png')));
    window.TINYMCE_CSRF_TOKEN = "{{ csrf_token() }}";
</script>
@vite(['resources/js/tinymce-init.js'])
