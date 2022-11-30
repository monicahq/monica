<?php

namespace App\Domains\Contact\Dav\Web\Backend;

use Illuminate\Support\Collection;

/**
 * @template TValue
 */
interface IDAVBackend
{
    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri(): string;

    /**
     * Returns the object for the specific uuid.
     *
     * @param  string|null  $collectionId
     * @param  string  $uuid
     * @return TValue
     */
    public function getObjectUuid(?string $collectionId, string $uuid);

    /**
     * Returns the collection of objects.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection
     */
    public function getObjects(?string $collectionId): Collection;

    /**
     * Returns the extension for this backend.
     *
     * @return string
     */
    public function getExtension(): string;
}
