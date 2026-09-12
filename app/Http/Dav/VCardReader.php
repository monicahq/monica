<?php

declare(strict_types=1);

namespace App\Http\Dav;

use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
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

    /** @return array<string, string|null> */
    public function nameParts(): array
    {
        $parts = $this->first('N')?->getParts() ?? [];

        return [
            'last_name' => $this->text($parts[0] ?? null),
            'first_name' => $this->text($parts[1] ?? null),
            'middle_name' => $this->text($parts[2] ?? null),
            'prefix' => $this->text($parts[3] ?? null),
            'suffix' => $this->text($parts[4] ?? null),
            'nickname' => $this->text((string) $this->first('NICKNAME')?->getValue()),
        ];
    }

    public function hasAName(): bool
    {
        $parts = $this->nameParts();
        unset($parts['nickname']);

        return array_filter($parts) !== [];
    }

    public function fallbackFirstName(): ?string
    {
        return $this->text((string) $this->first('FN')?->getValue());
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
}
