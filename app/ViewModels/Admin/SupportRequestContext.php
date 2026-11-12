<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\SupportRequestFacts;
use App\Models\SupportRequest;

class SupportRequestContext
{
    public function __construct(
        private readonly SupportRequest $supportRequest,
    ) {}

    public function name(): string
    {
        return $this->supportRequest->user->name ?? __('Deleted user');
    }

    public function email(): string
    {
        return $this->supportRequest->user->email ?? '';
    }

    public function accountUrl(): string
    {
        return route('admin.accounts.show', ['account' => $this->supportRequest->account_id]);
    }

    /** @return list<array{label: string, value: string}> */
    public function facts(): array
    {
        return SupportRequestFacts::of($this->supportRequest);
    }
}
