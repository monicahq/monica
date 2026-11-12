<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Add a kind of thing the account owns. Without a name it is called "Untitled type" in the language
 * of whoever creates it, and without a colour it takes the first vault colour. Its icon, when it has one,
 * comes from the catalogue.
 */
class CreatePossessionType
{
    private PossessionType $possessionType;

    public function __construct(
        private readonly User $user,
        private ?string $name = null,
        private readonly ?VaultColourEnum $colour = null,
        private ?string $icon = null,
    ) {}

    public function execute(): PossessionType
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->possessionType;
    }

    private function sanitize(): void
    {
        $this->name = $this->name === null
            ? __('Untitled type')
            : TextSanitizer::plainText($this->name);

        $this->icon = $this->icon === '' ? null : $this->icon;
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this possession type.'),
            ]);
        }

        if ($this->icon !== null && ! in_array($this->icon, Section::ICONS, true)) {
            throw ValidationException::withMessages([
                'icon' => __('Pick an icon from the list.'),
            ]);
        }
    }

    private function create(): void
    {
        $this->possessionType = PossessionType::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $this->name,
            'colour' => ($this->colour ?? VaultColourEnum::Blue)->value,
            'icon' => $this->icon,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTypeCreation,
            parameters: ['name' => $this->possessionType->name],
            loggable: $this->possessionType,
        )->onQueue('low');
    }
}
