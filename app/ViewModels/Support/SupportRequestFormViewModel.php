<?php

declare(strict_types=1);

namespace App\ViewModels\Support;

use App\Enums\SupportCategoryEnum;
use App\Helpers\SupportAvailability;
use App\Models\SupportAttachment;
use App\Models\User;

class SupportRequestFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $selectedCategory = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function navigation(): SupportNavigation
    {
        return new SupportNavigation($this->user);
    }

    public function administratorNames(): string
    {
        $names = SupportAvailability::administrators()->map(fn (User $administrator): string => $administrator->first_name)->all();

        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return __(':names and :last', ['names' => implode(', ', $names), 'last' => $last]);
    }

    public function administratorLine(): string
    {
        $count = SupportAvailability::administrators()->count();

        if ($count === 0) {
            return '';
        }

        return trans_choice('{1}Runs this instance, and replies in person as soon as possible.|All :count of them run this instance, and reply in person as soon as possible.', $count);
    }

    /** @return list<string> */
    public function portraitUrls(): array
    {
        return [
            asset('images/regis.webp'),
            asset('images/alexis.webp'),
        ];
    }

    public function selectedCategory(): string
    {
        $values = SupportCategoryEnum::values();

        return in_array($this->selectedCategory, $values, true) ? $this->selectedCategory : SupportCategoryEnum::Feature->value;
    }

    /** @return list<array{value: string, label: string, icon: string, heading: string, paragraphs: list<string>, subjectHint: string}>
     */
    public function categories(): array
    {
        return array_map(fn (SupportCategoryEnum $category): array => [
            'value' => $category->value,
            'label' => __($category->label()),
            'icon' => $category->icon(),
            'heading' => __($category->heading()),
            'paragraphs' => array_map(fn (string $paragraph): string => __($paragraph), $category->paragraphs()),
            'subjectHint' => __($category->subjectHint()),
        ], SupportCategoryEnum::cases());
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
}
