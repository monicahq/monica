<?php

namespace App\Contact\ManageDocuments\Listeners;

use App\Contact\ManageDocuments\Events\FileDeleted;
use App\Exceptions\EnvVariablesNotSetException;
use App\Models\File;
use Http\Client\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Uploadcare\Api;
use Uploadcare\Configuration;
use Uploadcare\Interfaces\File\FileInfoInterface;

class DeleteFileInStorage
{
    /**
     * The file instance.
     *
     * @var File
     */
    public File $file;

    /**
     * The file in Uploadcare instance.
     *
     * @var FileInfoInterface
     */
    public FileInfoInterface $fileInUploadcare;

    /**
     * The API used to query Uploadcare.
     *
     * @var Api
     */
    public Api $api;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  FileDeleted  $event
     */
    public function handle(FileDeleted $event)
    {
        $this->file = $event->file;
        $this->checkAPIKeyPresence();
        $this->getFileFromUploadcare();
        $this->deleteFile();
    }

    private function checkAPIKeyPresence(): void
    {
        if (is_null(config('services.uploadcare.private_key'))) {
            throw new EnvVariablesNotSetException();
        }

        if (is_null(config('services.uploadcare.public_key'))) {
            throw new EnvVariablesNotSetException();
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
