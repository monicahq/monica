<?php

declare(strict_types=1);

namespace App\Http\Dav;

use Override;
use Sabre\CardDAV\Plugin;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Parameter;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class CardDavPlugin extends Plugin
{
    /**
     * @param  string|resource  $data
     * @param  string  $target
     * @param  array<int, string>|null  $propertiesFilter
     */
    #[Override]
    protected function convertVCard($data, $target, ?array $propertiesFilter = null)
    {
        if ($target === 'vcard3') {
            $data = $this->withTheDisplayedNameOnly(is_resource($data) ? (string) stream_get_contents($data) : $data);
        }

        return parent::convertVCard($data, $target, $propertiesFilter);
    }

    /**
     * @param  string|resource  $data
     * @param  bool  $modified
     */
    #[Override]
    protected function validateVCard(&$data, &$modified)
    {
        if (is_resource($data)) {
            $data = (string) stream_get_contents($data);
        }

        if (str_starts_with($data, '[')) {
            try {
                $data = Reader::readJson($data)->serialize();
                $modified = true;
            } catch (ParseException) {
                parent::validateVCard($data, $modified);

                return;
            }
        }

        $probe = $this->withOneOfItsAlternativeNames($data);

        if ($probe === null) {
            parent::validateVCard($data, $modified);

            return;
        }

        parent::validateVCard($probe, $modified);
    }

    private function withOneOfItsAlternativeNames(string $data): ?string
    {
        try {
            $card = Reader::read($data, Reader::OPTION_FORGIVING);
        } catch (ParseException) {
            return null;
        }

        if (! $card instanceof VCard) {
            return null;
        }

        $names = iterator_to_array($card->select('N'), false);
        $formatted = iterator_to_array($card->select('FN'), false);
        $altIds = array_unique(array_map(fn (Property $property): string => $this->parameterOf($property, 'ALTID'), [...$names, ...$formatted]));

        if (count($names) <= 1 || count($altIds) !== 1 || reset($altIds) === '') {
            return null;
        }

        foreach ([...array_slice($names, 1), ...array_slice($formatted, 1)] as $alternative) {
            $card->remove($alternative);
        }

        return $card->serialize();
    }

    private function parameterOf(Property $property, string $name): string
    {
        $parameter = $property->parameters()[$name] ?? null;

        return $parameter instanceof Parameter ? implode(',', $parameter->getParts()) : '';
    }

    private function withTheDisplayedNameOnly(string $data): string
    {
        $card = Reader::read($data);

        if (! $card instanceof VCard || count($card->select('N')) <= 1) {
            return $data;
        }

        $formatted = iterator_to_array($card->select('FN'), false);
        $shown = collect($formatted)->first(fn (Property $property): bool => $this->parameterOf($property, 'PREF') === '1') ?? ($formatted[0] ?? null);
        $shownId = $shown === null ? '' : $this->parameterOf($shown, 'PROP-ID');

        $names = iterator_to_array($card->select('N'), false);
        $kept = collect($names)->first(fn (Property $property): bool => $shownId !== '' && $this->parameterOf($property, 'PROP-ID') === $shownId)
            ?? collect($names)->first(fn (Property $property): bool => $this->parameterOf($property, 'PHONETIC') === '');

        foreach ([...$names, ...$formatted] as $property) {
            if ($property === $kept || $property === $shown) {
                foreach (['ALTID', 'PROP-ID', 'PREF', 'X-NAME-KIND'] as $parameter) {
                    unset($property[$parameter]);
                }

                continue;
            }

            $card->remove($property);
        }

        return $card->serialize();
    }
}
