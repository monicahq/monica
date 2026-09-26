<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Stevebauman\Purify\Facades\Purify;

/**
 * Record an email that went out, keeping its copy without any link or web address, so a sign-in
 * link can never be read back from the record.
 */
class CreateEmailSent
{
    private EmailSent $emailSent;

    private string $updatedBody = '';

    public function __construct(
        private readonly Account $account,
        private readonly ?User $user,
        private readonly ?string $uuid,
        private readonly string $emailType,
        private readonly string $emailAddress,
        private readonly string $subject,
        private readonly string $body,
    ) {}

    public function execute(): EmailSent
    {
        $this->sanitize();
        $this->create();

        return $this->emailSent;
    }

    private function sanitize(): void
    {
        $config = ['HTML.ForbiddenElements' => 'a'];
        $withoutLinks = Purify::config($config)->clean($this->body);

        $this->updatedBody = preg_replace('~https?://[^\s<>"]+~i', '', $withoutLinks) ?? '';
    }

    private function create(): void
    {
        $this->emailSent = EmailSent::query()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user?->id,
            'uuid' => $this->uuid,
            'email_type' => $this->emailType,
            'email_address' => $this->emailAddress,
            'subject' => $this->subject,
            'body' => $this->updatedBody,
            'sent_at' => now(),
        ]);
    }
}
