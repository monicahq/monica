<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Traits;

use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;

trait HasClient
{
    private DavClient $client;

    /**
     * Set the dav client.
     */
    #[\ReturnTypeWillChange]
    public function withClient(DavClient $client): self
    {
        $this->client = $client;

        return $this;
    }
}
