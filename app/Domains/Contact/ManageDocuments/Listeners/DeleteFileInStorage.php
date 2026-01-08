<?php

namespace App\Domains\Contact\ManageDocuments\Listeners;

use App\Domains\Contact\ManageDocuments\Events\FileDeleted;
use App\Exceptions\EnvVariablesNotSetException;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Uploadcare\Api;
use Uploadcare\Configuration;
use Uploadcare\Interfaces\File\FileInfoInterface;

class DeleteFileInStorage
{
    /**
     * The file instance.
     */
    public File $file;

    /**
     * The file in Uploadcare instance.
     */
    public FileInfoInterface $fileInUploadcare;

    /**
     * The API used to query Uploadcare.
     */
    public Api $api;

    /**
     * Handle the event.
     */
    public function handle(FileDeleted $event)
    {
        $this->file = $event->file;
        
        // Check if file is stored locally (not on Uploadcare)
        if ($this->isLocalFile()) {
            $this->deleteLocalFile();
            return;
        }
        
        // Otherwise, delete from Uploadcare
        $this->checkAPIKeyPresence();
        $this->getFileFromUploadcare();
        $this->deleteFile();
    }

    private function isLocalFile(): bool
    {
        // Local files have asset() URLs, not Uploadcare CDN URLs
        return str_starts_with($this->file->cdn_url ?? '', config('app.url'));
    }

    private function deleteLocalFile(): void
    {
        // Extract the path from the URL
        // URL format: http://localhost:9092/storage/avatars/{vault_id}/{uuid}.{ext}
        $url = $this->file->cdn_url ?? $this->file->original_url;
        
        if (preg_match('#/storage/(.+)$#', $url, $matches)) {
            $path = $matches[1];
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function checkAPIKeyPresence(): void
    {
        if (is_null(config('services.uploadcare.private_key'))) {
            throw new EnvVariablesNotSetException;
        }

        if (is_null(config('services.uploadcare.public_key'))) {
            throw new EnvVariablesNotSetException;
        }
    }

    private function getFileFromUploadcare(): void
    {
        $configuration = Configuration::create(config('services.uploadcare.public_key'), config('services.uploadcare.private_key'));
        $this->api = new Api($configuration);

        try {
            $this->fileInUploadcare = $this->api->file()->fileInfo($this->file->uuid);
        } catch (HttpException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    private function deleteFile(): void
    {
        $this->api->file()->deleteFile($this->fileInUploadcare);
    }
}
