<?php

namespace Tests\Feature;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminUploadFlowTest extends TestCase
{
    protected User $admin;

    protected array $createdUploadIds = [];

    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->findOrFail(1);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdUploadIds as $uploadId) {
            if ($upload = Upload::query()->find($uploadId)) {
                $upload->deleteStoredFile();
                $upload->delete();
            }
        }

        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_admin_can_upload_and_list_a_jpg_with_preview_url(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => UploadedFile::fake()->image('e2e-photo.jpg', 600, 600),
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'file' => [
                    'extension' => 'jpg',
                    'type' => 'image',
                    'is_previewable' => true,
                    'file_original_name' => 'e2e-photo',
                ],
            ]);

        $uploadId = $response->json('id');
        $this->createdUploadIds[] = $uploadId;
        $upload = Upload::query()->findOrFail($uploadId);

        $this->assertTrue($upload->fileExists());
        $this->assertSame('e2e-photo', $upload->display_name);

        $this->actingAs($this->admin)
            ->get('/aiz-uploader/get_uploaded_files?sort=newest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $uploadId)
            ->assertJsonPath('data.0.preview_url', route('uploads.file', ['upload' => $uploadId]))
            ->assertJsonPath('data.0.file_original_name', 'e2e-photo');

        $this->get(route('uploads.file', ['upload' => $uploadId]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs($this->admin)
            ->get('/admin/uploaded-files')
            ->assertOk()
            ->assertSee(route('uploads.file', ['upload' => $uploadId]), false)
            ->assertSee('e2e-photo', false);
    }

    public function test_supported_image_types_and_document_fallback_are_serialized_consistently(): void
    {
        $pngId = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => UploadedFile::fake()->image('diagram.png', 300, 300),
            ])->json('id');
        $this->createdUploadIds[] = $pngId;

        $svgId = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => new UploadedFile(public_path('assets/img/logo.svg'), 'logo.svg', 'image/svg+xml', null, true),
            ])->json('id');
        $this->createdUploadIds[] = $svgId;

        $webpId = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => new UploadedFile(public_path('uploads/all/7qliQVrG2urDWMtI9Kzw90asXd7c7U9yFvNML9dG.webp'), 'sample.webp', 'image/webp', null, true),
            ])->json('id');
        $this->createdUploadIds[] = $webpId;

        $pdfResponse = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => new UploadedFile(public_path('invoices/order-invoice-20230927-05154224.pdf'), 'invoice.pdf', 'application/pdf', null, true),
            ]);

        $pdfResponse->assertOk()
            ->assertJsonPath('file.type', 'document')
            ->assertJsonPath('file.is_previewable', false)
            ->assertJsonPath('file.file_icon_class', 'las la-file-alt');

        $pdfId = $pdfResponse->json('id');
        $this->createdUploadIds[] = $pdfId;

        $this->actingAs($this->admin)
            ->get('/admin/uploaded-files')
            ->assertOk()
            ->assertSee(route('uploads.file', ['upload' => $pdfId, 'download' => 1]), false)
            ->assertSee('las la-file-alt', false)
            ->assertSee(route('uploads.file', ['upload' => $pngId]), false)
            ->assertSee(route('uploads.file', ['upload' => $svgId]), false)
            ->assertSee(route('uploads.file', ['upload' => $webpId]), false);
    }

    public function test_invalid_file_type_fails_gracefully_without_creating_a_record(): void
    {
        $badFile = storage_path('app/testing-invalid-upload.php');
        File::put($badFile, '<?php echo "bad";');
        $this->createdFiles[] = $badFile;

        $beforeCount = Upload::query()->count();

        $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => new UploadedFile($badFile, 'payload.php', 'text/x-php', null, true),
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'This file type is not supported.',
            ]);

        $this->assertSame($beforeCount, Upload::query()->count());
    }

    public function test_legacy_public_prefixed_records_render_without_double_extensions_and_preview(): void
    {
        $upload = Upload::query()->create([
            'file_original_name' => 'legacy-sample.jpg',
            'file_name' => 'public/assets/img/about1.jpg',
            'user_id' => $this->admin->id,
            'file_size' => filesize(public_path('assets/img/about1.jpg')),
            'extension' => 'jpg',
            'type' => 'image',
        ]);

        $this->createdUploadIds[] = $upload->id;

        $this->assertSame('legacy-sample', $upload->display_name);
        $this->assertTrue($upload->fileExists());

        $this->actingAs($this->admin)
            ->get('/admin/uploaded-files')
            ->assertOk()
            ->assertSee('legacy-sample.jpg', false)
            ->assertDontSee('legacy-sample.jpg.jpg', false)
            ->assertSee(route('uploads.file', ['upload' => $upload->id]), false);
    }

    public function test_delete_route_removes_the_record_and_stored_file(): void
    {
        $uploadId = $this->actingAs($this->admin)
            ->post('/aiz-uploader/upload', [
                'aiz_file' => UploadedFile::fake()->image('delete-me.jpg', 200, 200),
            ])->json('id');

        $upload = Upload::query()->findOrFail($uploadId);
        $storedPath = $upload->absolutePath();

        $this->assertNotNull($storedPath);
        $this->assertFileExists($storedPath);

        $this->actingAs($this->admin)
            ->get(route('uploaded-files.destroy', $uploadId))
            ->assertRedirect();

        $this->assertDatabaseMissing('uploads', ['id' => $uploadId]);
        $this->assertFalse($storedPath && file_exists($storedPath));
    }
}
