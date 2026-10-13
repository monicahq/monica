<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http\Dav;

use App\Http\Dav\CardDavPlugin;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;

class ExposedCardDavPlugin extends CardDavPlugin
{
    public function convert(string $data, string $target): string
    {
        return $this->convertVCard($data, $target);
    }

    public function validate(string $data, ?string $prefer = null): string
    {
        $this->server = new Server;
        $this->server->httpRequest = new Request('PUT', '/card.vcf', $prefer === null ? [] : ['Prefer' => $prefer]);
        $modified = false;

        $this->validateVCard($data, $modified);

        return $data;
    }
}
