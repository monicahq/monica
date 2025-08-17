<?php

namespace App\Domains\Contact\Dav\Web\Backend;

use Illuminate\Support\Collection;

/**
 * @template TValue
 */
interface IDAVBackend
{
    /**
     * Returns the id for this backend.
     */
    public function backendId(?string $collectionId = null): string;

    /**
     * Returns the uri for this backend.
     */
    public function backendUri(): string;

    /**
     * Returns the object for the specific uuid.
     *
     * @return TValue
     */
    public function getObjectUuid(?string $collectionId, string $uuid);

    /**
     * Returns the collection of objects.
     */
    public function getObjects(?string $collectionId): Collection;

    /**
     * Returns the extension for this backend.
     */
    public function getExtension(): string;
}
