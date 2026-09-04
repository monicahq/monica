<?php

declare(strict_types=1);

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Override;

/**
 * The mail message the notifications in this application build.
 *
 * It adds the panel, the boxed paragraph the security emails end on to carry
 * the "if this was not you" warning. Laravel's builder knows a greeting, intro
 * lines, an action and outro lines, and has no way to say "box this one".
 *
 * It also picks the mailer. Where Resend is switched on, its transport writes
 * the message id back onto the message, and that id is what the delivery
 * webhooks are matched against later. Anywhere else the configured default is
 * used, so a self-hosted instance needs no Resend account.
 */
class MailMessage extends BaseMailMessage
{
    public ?string $panel = null;

    /**
     * Box a paragraph, and render it after the button and before the outro
     * lines.
     */
    public function panel(string $panel): static
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * Add a line that reads after the panel.
     *
     * `line()` cannot: it decides between an intro line and an outro line by
     * whether an action has been set, and an email that has a panel and no
     * button would put every line before it.
     */
    public function outroLine(string $line): static
    {
        $this->outroLines[] = $this->formatLine($line);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        return array_merge(parent::data(), ['panel' => $this->panel]);
    }
}
