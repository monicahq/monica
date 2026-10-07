<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http\Dav;

use App\Http\Dav\CardDavPlugin;

class ExposedCardDavPlugin extends CardDavPlugin
{
    public function convert(string $data, string $target): string
    {
        return $this->convertVCard($data, $target);
    }
}
