<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum SupportCategoryEnum: string implements Translatable
{
    case Feature = 'feature';
    case Billing = 'billing';
    case Email = 'email';
    case Question = 'question';
    case Technical = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::Feature => 'I want to request a feature',
            self::Billing => 'I have a billing question',
            self::Email => 'I’m not receiving emails',
            self::Question => 'I’m confused about how something works',
            self::Technical => 'I think something is broken',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Feature => 'Feature request',
            self::Billing => 'Billing',
            self::Email => 'Missing emails',
            self::Question => 'Question',
            self::Technical => 'Something broken',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Feature => 'sparkles',
            self::Billing => 'credit-card',
            self::Email => 'mail',
            self::Question => 'question',
            self::Technical => 'bug',
        };
    }

    public function heading(): string
    {
        return match ($this) {
            self::Feature => 'We’d love to hear your ideas.',
            self::Billing => 'Let’s sort out your billing.',
            self::Email => 'Let’s find your missing emails.',
            self::Question => 'Ask away.',
            self::Technical => 'Let’s fix what’s broken.',
        };
    }

    /** @return list<string> */
    public function paragraphs(): array
    {
        return match ($this) {
            self::Feature => [
                'Tell us what you’d like to see, but also tell us why it matters to you. Understanding the problem is often more valuable than the solution itself.',
                'We can’t promise every idea will make it into the app, but we promise we’ll read every single one.',
            ],
            self::Billing => [
                'Tell us which payment, invoice or plan this is about, and the date if you have it.',
                'Never send your full card number. We never need it to help you.',
            ],
            self::Email => [
                'Before writing, check your spam folder and the address shown in your profile settings.',
                'If they’re not there, tell us which emails you expected and roughly when. We can trace them from there.',
            ],
            self::Question => [
                'Ask it the way you’d ask a friend who knows Monica well: what you were trying to do, and where you got stuck.',
            ],
            self::Technical => [
                'Tell us what you did, what you expected, and what happened instead.',
                'The page you were on and the browser you use help us reproduce it much faster.',
            ],
        };
    }

    public function subjectHint(): string
    {
        return match ($this) {
            self::Feature => 'For example: Sort people by last contacted',
            self::Billing => 'For example: October invoice shows the wrong company name',
            self::Email => 'For example: Reminder emails stopped in October',
            self::Question => 'For example: Can I share one contact without sharing the vault?',
            self::Technical => 'For example: Import keeps timing out',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [
            $this->label(),
            $this->shortLabel(),
            $this->heading(),
            ...$this->paragraphs(),
            $this->subjectHint(),
        ];
    }
}
