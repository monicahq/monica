<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a security key, so its owner can tell their keys apart.
 */
class UpdateSecurityKey
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly SecurityKey $securityKey,
        string $name,
    ) {
        $this->name = TextSanitizer::plainText($name);
    }

    public function execute(): SecurityKey
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->securityKey;
    }

    private function validate(): void
    {
        if ($this->securityKey->user_id !== $this->user->id) {
            throw new ModelNotFoundException('Security key not found');
        }
    }

    private function update(): void
    {
        $this->securityKey->name = $this->name;
        $this->securityKey->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SecurityKeyRenamed,
            parameters: ['name' => $this->securityKey->name],
            loggable: $this->securityKey,
        );
    }
}
