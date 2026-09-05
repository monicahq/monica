<?php

declare(strict_types=1);

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Override;

class MailMessage extends BaseMailMessage
{
    public ?string $panel = null;

    public function panel(string $panel): static
    {
        $this->panel = $panel;

        return $this;
    }

    public function outroLine(string $line): static
    {
        $this->outroLines[] = $this->formatLine($line);

        return $this;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function data(): array
    {
        return array_merge(parent::data(), ['panel' => $this->panel]);
    }
}
