<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;

class ModerationSubmissionViewModel
{
    public function __construct(
        private readonly MarketplaceListingVersion $version,
    ) {}

    public function version(): MarketplaceListingVersion
    {
        return $this->version;
    }

    public function listing(): MarketplaceListing
    {
        return $this->version->listing;
    }

    public function payload(): string
    {
        return (string) json_encode(
            $this->version->payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    public function awaitsDecision(): bool
    {
        return $this->version->status->awaitsModeration();
    }
}
