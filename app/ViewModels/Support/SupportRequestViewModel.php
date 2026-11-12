<?php

declare(strict_types=1);

namespace App\ViewModels\Support;

use App\Enums\SupportRequestStatusEnum;
use App\Helpers\StorageSize;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupportRequestViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly SupportRequest $supportRequest,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function navigation(): SupportNavigation
    {
        return new SupportNavigation($this->user);
    }

    public function supportRequest(): SupportRequest
    {
        return $this->supportRequest;
    }

    public function categoryLabel(): string
    {
        return __($this->supportRequest->category->shortLabel());
    }

    public function statusLabel(): string
    {
        return __($this->supportRequest->status->userLabel());
    }

    public function statusTone(): string
    {
        return $this->supportRequest->status->userTone();
    }

    public function statusIcon(): string
    {
        return $this->supportRequest->status->icon();
    }

    public function openedOn(): string
    {
        return $this->supportRequest->created_at->setTimezone($this->user->timezone)->isoFormat('LL');
    }

    public function isSolved(): bool
    {
        return $this->supportRequest->status === SupportRequestStatusEnum::Solved;
    }

    public function solving(): Response
    {
        return $this->user->inspect('solve', $this->supportRequest);
    }

    public function maximumFiles(): int
    {
        return SupportAttachment::MAXIMUM_PER_MESSAGE;
    }

    public function maximumKilobytes(): int
    {
        return SupportAttachment::MAXIMUM_SIZE_IN_KILOBYTES;
    }

    public function acceptedTypes(): string
    {
        return collect(explode(',', SupportAttachment::ACCEPTED_TYPES))
            ->map(fn (string $extension): string => ".{$extension}")
            ->implode(',');
    }

    /** @return list<array{id: string, author: string, isSupportTeam: bool, when: string, body: string, files: list<array{name: string, size: string, url: string}>}>
     */
    public function messages(): array
    {
        return $this->supportRequest->visibleMessages()
            ->with('attachments')
            ->get()
            ->map(fn (SupportMessage $message): array => [
                'id' => $message->uuid,
                'author' => $message->author_name,
                'isSupportTeam' => $message->isFromSupportTeam(),
                'when' => $message->created_at->setTimezone($this->user->timezone)->isoFormat('LLL'),
                'body' => $message->body,
                'files' => $message->attachments->map(fn (SupportAttachment $attachment): array => [
                    'name' => $attachment->original_name,
                    'size' => StorageSize::readable($attachment->size_in_bytes),
                    'url' => route('support.attachments.show', $attachment),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
