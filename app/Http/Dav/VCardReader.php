<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Actions\UpdateContactNameRepresentations;
use App\Enums\NameOrderEnum;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\Parameter;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class VCardReader
{
    private VCard $card;

    public function __construct(string $card)
    {
        $this->card = $this->parse($card);
    }

    public function document(): VCard
    {
        return $this->card;
    }

    /**
     * @return list<array{kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null, vcard_alt_id: string|null, vcard_property_id: string|null, is_preferred: bool}>
     */
    public function nameRepresentations(): array
    {
        $formatted = $this->propertiesOf('FN');
        $rows = [];

        foreach ($this->propertiesOf('N') as $name) {
            $phonetic = $this->parameter($name, 'PHONETIC');
            $partner = $phonetic === null ? $this->partnerOf($name, $formatted) : null;

            if ($partner !== null) {
                $formatted = array_values(array_filter($formatted, fn (Property $candidate): bool => $candidate !== $partner));
            }

            $rows[] = $this->rowFrom($name, $partner);
        }

        foreach ($formatted as $alone) {
            $rows[] = $this->rowFrom(null, $alone);
        }

        return array_values(array_filter($rows, fn (array $row): bool => array_filter([$row['formatted_name'], $row['prefix'], $row['given_name'], $row['additional_name'], $row['family_name'], $row['suffix']]) !== []));
    }

    public function nickname(): ?string
    {
        return $this->text((string) $this->first('NICKNAME')?->getValue());
    }

    /** @return array<int, Property> */
    public function propertiesOf(string $property): array
    {
        return array_values(iterator_to_array($this->card->select($property), false));
    }

    public function first(string $property): ?Property
    {
        foreach ($this->card->select($property) as $found) {
            if ($found instanceof Property) {
                return $found;
            }
        }

        return null;
    }

    public function photo(): ?string
    {
        $photo = $this->first('PHOTO');

        if ($photo === null) {
            return null;
        }

        $value = (string) $photo->getValue();

        if (str_starts_with($value, 'data:')) {
            $encoded = (string) preg_replace('/^data:[^,]*,/', '', $value);

            return base64_decode($encoded, true) ?: null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return null;
        }

        return base64_decode($value, true) ?: null;
    }

    private function parse(string $card): VCard
    {
        try {
            $document = Reader::read($card, Reader::OPTION_FORGIVING);
        } catch (ParseException $exception) {
            throw new BadRequest('That is not a card this address book can read.', 0, $exception);
        }

        if (! $document instanceof VCard) {
            throw new BadRequest('That is not a card.');
        }

        $converted = $document->convert(Document::VCARD40);

        if (! $converted instanceof VCard) {
            throw new BadRequest('That card cannot be read as vCard 4.0.');
        }

        return $converted;
    }

    private function text(?string $value): ?string
    {
        return $value === null || mb_trim($value) === '' ? null : mb_trim($value);
    }

    /** @param  array<int, Property>  $formatted */
    private function partnerOf(Property $name, array $formatted): ?Property
    {
        $propertyId = $this->parameter($name, 'PROP-ID');

        foreach ($formatted as $candidate) {
            if ($propertyId !== null && $this->parameter($candidate, 'PROP-ID') === $propertyId) {
                return $candidate;
            }
        }

        $unnamed = array_values(array_filter($formatted, fn (Property $candidate): bool => $this->parameter($candidate, 'PROP-ID') === null));

        foreach ($unnamed as $candidate) {
            if (mb_strtolower((string) $this->parameter($candidate, 'LANGUAGE')) === mb_strtolower((string) $this->parameter($name, 'LANGUAGE'))
                && $this->script($candidate) === $this->script($name)) {
                return $candidate;
            }
        }

        foreach ($unnamed as $candidate) {
            if ($this->parameter($candidate, 'LANGUAGE') === null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null, vcard_alt_id: string|null, vcard_property_id: string|null, is_preferred: bool}
     */
    private function rowFrom(?Property $name, ?Property $formatted): array
    {
        $parts = $name?->getParts() ?? [];
        $source = $name ?? $formatted;
        $phonetic = $name === null ? null : $this->parameter($name, 'PHONETIC');
        $transliterated = $source !== null && mb_strtolower((string) $this->parameter($source, 'X-NAME-KIND')) === 'transliteration';

        $kind = match (true) {
            $phonetic !== null => 'phonetic',
            $transliterated => 'transliteration',
            default => 'written',
        };

        $row = [
            'kind' => $kind,
            'language' => $this->language($source),
            'script' => $source === null ? null : $this->script($source),
            'phonetic_system' => $phonetic === null ? null : mb_substr(mb_strtolower($phonetic), 0, 32),
            'formatted_name' => $this->cut($formatted === null ? null : (string) $formatted->getValue(), 255),
            'prefix' => $this->cut($parts[3] ?? null, 50),
            'given_name' => $this->cut($parts[1] ?? null, 255),
            'additional_name' => $this->cut($parts[2] ?? null, 255),
            'family_name' => $this->cut($parts[0] ?? null, 255),
            'suffix' => $this->cut($parts[4] ?? null, 50),
            'name_order' => null,
            'vcard_alt_id' => $source === null ? null : $this->cut($this->parameter($source, 'ALTID'), 64),
            'vcard_property_id' => $source === null ? null : $this->cut($this->parameter($source, 'PROP-ID'), 255),
            'is_preferred' => $formatted !== null && $this->parameter($formatted, 'PREF') === '1',
        ];

        $order = $row['formatted_name'] === null ? null : $this->orderComposing($row, $row['formatted_name']);

        if ($order !== null) {
            $row['formatted_name'] = null;
            $row['name_order'] = $order === NameOrderEnum::FamilyFirst ? $order->value : null;
        }

        return $row;
    }

    /** @param  array<string, mixed>  $row */
    private function orderComposing(array $row, string $formatted): ?NameOrderEnum
    {
        $given = mb_trim("{$row['given_name']} {$row['additional_name']}");
        $readings = [
            NameOrderEnum::GivenFirst->value => [$row['prefix'], $given, $row['family_name'], $row['suffix']],
            NameOrderEnum::FamilyFirst->value => [$row['prefix'], $row['family_name'], $given, $row['suffix']],
        ];
        $normalised = (string) preg_replace('/\s+/u', ' ', $formatted);

        foreach ($readings as $order => $reading) {
            if (implode(' ', array_filter($reading, fn (?string $part): bool => $part !== null && $part !== '')) === $normalised) {
                return NameOrderEnum::from($order);
            }
        }

        return null;
    }

    private function language(?Property $source): ?string
    {
        $tag = $source === null ? null : $this->parameter($source, 'LANGUAGE');

        if ($tag === null || mb_strlen($tag) > 35 || preg_match(UpdateContactNameRepresentations::LANGUAGE_PATTERN, $tag) !== 1) {
            return null;
        }

        return $tag;
    }

    private function script(Property $source): ?string
    {
        $code = $this->parameter($source, 'SCRIPT');

        if ($code === null) {
            return null;
        }

        $code = ucfirst(mb_strtolower($code));

        return preg_match(UpdateContactNameRepresentations::SCRIPT_PATTERN, $code) === 1 ? $code : null;
    }

    private function cut(?string $value, int $length): ?string
    {
        $value = $this->text($value);

        return $value === null ? null : $this->text(mb_substr($value, 0, $length));
    }

    private function parameter(Property $property, string $name): ?string
    {
        $parameter = $property->parameters()[$name] ?? null;

        if (! $parameter instanceof Parameter) {
            return null;
        }

        return $this->text(implode(',', $parameter->getParts()));
    }
}
