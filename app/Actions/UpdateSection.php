<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a section, say what it is for, or change the mark it is drawn with.
 *
 * None of this touches what a template version renders. The name here is the name of the
 * section itself, and a template version carrying it may already be showing a title of its
 * own instead. The slug is left alone, for the same reason a template keeps
 * its own.
 *
 * The mark is the glyph and the colour, and it belongs to the section rather
 * than to any placement of it, so changing it reaches every template carrying
 * the section. It decides nothing about what the section asks for, which is why
 * it gives the section no new version and leaves every stored value untouched.
 * Passing neither clears both, since the mark is set and cleared in one gesture.
 */
class UpdateSection
{
    public function __construct(
        private readonly User $user,
        private readonly Section $section,
        private string $name,
        private ?string $description = null,
        private ?string $icon = null,
        private ?string $colour = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): Section
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->section;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->icon = TextSanitizer::nullablePlainText($this->icon);
        $this->colour = TextSanitizer::nullablePlainText($this->colour);
    }

    private function validate(): void
    {
        if ($this->section->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section is in another account.');
        }
    }

    private function update(): void
    {
        $this->section->name = $this->name;
        $this->section->description = $this->description;
        $this->section->icon = $this->icon;
        $this->section->colour = $this->colour;
        $this->section->locale = $this->locale ?? $this->section->locale;
        $this->section->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionUpdate,
            parameters: ['name' => $this->section->name],
            loggable: $this->section,
        )->onQueue('low');
    }
}
