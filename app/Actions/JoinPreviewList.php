<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PreviewListEntry;
use App\Models\User;

/**
 * Put an email address on the preview list, unless it is already there or already holds an account,
 * in which case nothing happens so that the visitor cannot tell the difference.
 */
class JoinPreviewList
{
    public function __construct(
        private string $email,
        private readonly string $locale,
    ) {}

    public function execute(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        if ($this->isAlreadyKnown()) {
            return;
        }

        PreviewListEntry::query()->create([
            'email' => $this->email,
            'locale' => $this->locale,
        ]);
    }

    private function isAlreadyKnown(): bool
    {
        return User::query()->where('email', $this->email)->exists()
            || PreviewListEntry::query()->where('email', $this->email)->exists();
    }
}
