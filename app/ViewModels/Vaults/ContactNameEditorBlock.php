<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\UpdateContactNameRepresentations;
use App\Enums\ContactNameKindEnum;
use App\Enums\NameOrderEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Locale;

class ContactNameEditorBlock
{
    public const array LANGUAGES = ['ar', 'zh', 'en', 'fr', 'el', 'hi', 'ja', 'ko', 'ru'];

    public const array SCRIPTS = ['Arab', 'Hani', 'Cyrl', 'Deva', 'Grek', 'Hang', 'Hira', 'Kana', 'Latn'];

    public const array PHONETIC_SYSTEMS = ['ipa', 'piny', 'x-romaji', 'x-rr', 'x-other'];

    public function __construct(
        private readonly ?Contact $contact = null,
    ) {}

    /**
     * @return list<array{id: string|null, kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null}>
     */
    public function rows(): array
    {
        $old = old('names');

        if (is_array($old)) {
            return array_values(array_map(fn (mixed $row): array => $this->rowFromOldInput(is_array($row) ? $row : []), $old));
        }

        if (! $this->contact instanceof Contact) {
            return [];
        }

        return $this->contact->nameRepresentations
            ->map(fn (ContactNameRepresentation $representation): array => ['id' => $representation->uuid, ...$representation->toComparable()])
            ->values()
            ->all();
    }

    public function display(): ?int
    {
        $old = old('display');

        if ($old !== null) {
            return (int) $old;
        }

        if (! $this->contact instanceof Contact) {
            return null;
        }

        $index = $this->contact->nameRepresentations->search(fn (ContactNameRepresentation $representation): bool => $representation->id === $this->contact->display_name_representation_id);

        return $index === false ? null : $index;
    }

    public function hadAName(): bool
    {
        return $this->contact instanceof Contact && $this->contact->display_name_representation_id !== null;
    }

    /** @return array<string, string> */
    public function languages(): array
    {
        $codes = [...self::LANGUAGES, ...array_column($this->rows(), 'language')];

        return $this->labelled($codes, UpdateContactNameRepresentations::LANGUAGE_PATTERN, fn (string $code): string => (string) Locale::getDisplayLanguage($code, App::getLocale()));
    }

    /** @return array<string, string> */
    public function scripts(): array
    {
        $codes = [...self::SCRIPTS, ...array_column($this->rows(), 'script')];

        return $this->labelled($codes, UpdateContactNameRepresentations::SCRIPT_PATTERN, fn (string $code): string => (string) Locale::getDisplayScript("und-{$code}", App::getLocale()));
    }

    /** @return array<string, string> */
    public function phoneticSystems(): array
    {
        $systems = [
            'ipa' => __('International Phonetic Alphabet'),
            'piny' => __('Pinyin'),
            'x-romaji' => __('Rōmaji'),
            'x-rr' => __('Revised Romanization of Korean'),
            'x-other' => __('Something else'),
        ];

        foreach (array_filter(array_column($this->rows(), 'phonetic_system')) as $token) {
            $systems[$token] ??= $token;
        }

        return $systems;
    }

    /** @return array<string, string> */
    public function kinds(): array
    {
        $kinds = [];

        foreach (ContactNameKindEnum::cases() as $kind) {
            $kinds[$kind->value] = __($kind->label());
        }

        return $kinds;
    }

    /** @return array<string, string> */
    public function nameOrders(): array
    {
        $orders = ['' => __('Follow my preference')];

        foreach (NameOrderEnum::cases() as $order) {
            $orders[$order->value] = __($order->label());
        }

        return $orders;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: string|null, kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null}
     */
    private function rowFromOldInput(array $row): array
    {
        return [
            'id' => $this->textIn($row, 'id'),
            'kind' => $this->textIn($row, 'kind') ?? ContactNameKindEnum::Written->value,
            'language' => $this->textIn($row, 'language'),
            'script' => $this->textIn($row, 'script'),
            'phonetic_system' => $this->textIn($row, 'phonetic_system'),
            'formatted_name' => $this->textIn($row, 'formatted_name'),
            'prefix' => $this->textIn($row, 'prefix'),
            'given_name' => $this->textIn($row, 'given_name'),
            'additional_name' => $this->textIn($row, 'additional_name'),
            'family_name' => $this->textIn($row, 'family_name'),
            'suffix' => $this->textIn($row, 'suffix'),
            'name_order' => $this->textIn($row, 'name_order'),
        ];
    }

    /** @param array<string, mixed> $row */
    private function textIn(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<int, string|null>  $codes
     * @param  callable(string): string  $name
     * @return array<string, string>
     */
    private function labelled(array $codes, string $pattern, callable $name): array
    {
        $labels = [];

        foreach (array_unique(array_filter($codes)) as $code) {
            if (preg_match($pattern, $code) !== 1) {
                continue;
            }

            $label = $name($code) ?: $code;
            $labels[$code] = Str::ucfirst($label);
        }

        asort($labels);

        return $labels;
    }
}
