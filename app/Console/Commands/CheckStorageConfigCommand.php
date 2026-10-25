<?php

namespace App\Console\Commands;

use App\Services\MediaUploadService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheckStorageConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:check {--test : Perform a live upload & cleanup test with the active provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and check configuration for Cloudinary and AWS S3 storage providers';

    public function handle(MediaUploadService $mediaService): int
    {
        $this->info("=================================================");
        $this->info("      ESTATEHUB MEDIA STORAGE AUDIT CHECK        ");
        $this->info("=================================================");

        $activeProvider = $mediaService->getActiveProviderName();
        $this->line("Active Storage Provider (via env): <fg=cyan;options=bold>{$activeProvider}</>");
        $this->newLine();

        // 1. Audit Cloudinary Config
        $this->comment("--- [1] Cloudinary Configuration ---");
        $cloudName = config('cloudinary.cloud_name');
        $cApiKey = config('cloudinary.api_key');
        $cSecret = config('cloudinary.api_secret');
        $cFolder = config('cloudinary.folder');

        $cConfigured = !empty($cloudName) && !empty($cApiKey) && !empty($cSecret);

        $this->table(
            ['Parameter', 'Status / Value'],
            [
                ['CLOUDINARY_CLOUD_NAME', $cloudName ?: '<fg=red>Not Set</>'],
                ['CLOUDINARY_API_KEY', $cApiKey ? substr($cApiKey, 0, 4) . '****' : '<fg=red>Not Set</>'],
                ['CLOUDINARY_API_SECRET', $cSecret ? '****' . substr($cSecret, -4) : '<fg=red>Not Set</>'],
                ['CLOUDINARY_FOLDER', $cFolder ?: 'estate-hub/properties'],
                ['Overall Status', $cConfigured ? '<fg=green>Configured</>' : '<fg=yellow>Incomplete</>'],
            ]
        );

        $this->newLine();

        // 2. Audit AWS S3 Config
        $this->comment("--- [2] AWS S3 Configuration ---");
        $s3Key = config('filesystems.disks.s3.key');
        $s3Secret = config('filesystems.disks.s3.secret');
        $s3Region = config('filesystems.disks.s3.region') ?: 'us-east-1';
        $s3Bucket = config('filesystems.disks.s3.bucket');
        $s3Url = config('filesystems.disks.s3.url');
        $s3Prefix = config('media.s3.prefix', 'estate-hub');

        $s3Configured = !empty($s3Key) && !empty($s3Secret) && !empty($s3Bucket);

        $this->table(
            ['Parameter', 'Status / Value'],
            [
                ['AWS_ACCESS_KEY_ID', $s3Key ? substr($s3Key, 0, 4) . '****' : '<fg=red>Not Set</>'],
                ['AWS_SECRET_ACCESS_KEY', $s3Secret ? '****' . substr($s3Secret, -4) : '<fg=red>Not Set</>'],
                ['AWS_DEFAULT_REGION', $s3Region],
                ['AWS_BUCKET', $s3Bucket ?: '<fg=red>Not Set</>'],
                ['AWS_URL (CDN / CloudFront)', $s3Url ?: '<fg=gray>Direct S3 URL</>'],
                ['AWS_S3_FOLDER (Prefix)', $s3Prefix],
                ['Overall Status', $s3Configured ? '<fg=green>Configured</>' : '<fg=yellow>Incomplete (Provide AWS credentials in .env)</>'],
            ]
        );

        $this->newLine();

        // 3. Live Test Upload if requested
        if ($this->option('test')) {
            $this->comment("--- [3] Performing Live Upload Test on Active Provider: [{$activeProvider}] ---");

            try {
                // Generate a temporary 1x1 transparent PNG in memory
                $tempPath = tempnam(sys_get_temp_dir(), 's3_test_');
                $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
                file_put_contents($tempPath, $pngData);

                $testFile = new UploadedFile(
                    $tempPath,
                    'audit-test-image.png',
                    'image/png',
                    null,
                    true
                );

                $this->line("Uploading test image...");
                $result = $mediaService->uploadFeedbackScreenshot($testFile);

                $this->info(" Upload Successful!");
                $this->line("   - Public URL: " . $result['secure_url']);
                $this->line("   - Identifier: " . $result['public_id']);

                // Cleanup
                $this->line("Cleaning up test image...");
                $mediaService->delete($result['public_id']);
                $this->info(" Test image cleanup completed!");

                @unlink($tempPath);
            } catch (Throwable $e) {
                $this->error(" Upload Test Failed: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->line("Tip: Run <fg=yellow>php artisan storage:check --test</> to perform a live upload & cleanup test.");
        }

        $this->newLine();
        return 0;
    }
}
