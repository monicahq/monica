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

    public function title(): string
    {
        return $this->listing()->name;
    }

    /** Who wrote it, what it is filed under, which version this is and when it arrived. */
    public function provenance(): string
    {
        return implode(' · ', [
            $this->listing()->author_name,
            $this->listing()->category->name,
            __('version :version', ['version' => $this->version->version_number]),
            __('submitted :date', ['date' => $this->version->created_at->isoFormat('LL')]),
        ]);
    }

    public function summary(): string
    {
        return $this->listing()->summary;
    }

    public function description(): string
    {
        return $this->listing()->description;
    }

    public function changeNote(): ?string
    {
        return $this->version->change_note;
    }

    public function approvalUrl(): string
    {
        return route('admin.moderation.approval.create', ['version' => $this->version->id]);
    }

    public function rejectionUrl(): string
    {
        return route('admin.moderation.rejection.create', ['version' => $this->version->id]);
    }

    /** Why there is nothing to decide, for a version somebody has already decided. */
    public function decidedLine(): string
    {
        return __('This version is :status, so there is nothing to decide. :moderator decided it.', [
            'status' => $this->version->status->value,
            'moderator' => $this->listing()->moderated_by ?? __('Somebody else'),
        ]);
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
