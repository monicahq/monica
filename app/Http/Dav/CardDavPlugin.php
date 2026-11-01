<?php

declare(strict_types=1);

namespace App\Http\Dav;

use Override;
use Sabre\CardDAV\Plugin;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\Node;
use Sabre\VObject\Parameter;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class CardDavPlugin extends Plugin
{
    private const string UNREADABLE_LINE = 'X-MONICA-UNREADABLE-LINE';

    private const int MOST_UNREADABLE_LINES = 50;

    /**
     * @param  string|resource  $data
     * @param  string  $target
     * @param  array<int, string>|null  $propertiesFilter
     */
    #[Override]
    protected function convertVCard($data, $target, ?array $propertiesFilter = null)
    {
        if ($target === 'vcard3') {
            $data = $this->withoutTheAppleGroupForm($this->withTheDisplayedNameOnly(is_resource($data) ? (string) stream_get_contents($data) : $data));
        }

        $converted = parent::convertVCard($data, $target, $propertiesFilter);

        if ($target === 'jcard') {
            return $converted;
        }

        return $this->withTheUnreadableLinesAsSent($converted);
    }

    /**
     * A device whose card is refused retries it forever, so every card that reads as one is
     * repaired and accepted. Only a client asking for strict handling still gets the refusal.
     *
     * @param  string|resource  $data
     * @param  bool  $modified
     */
    #[Override]
    protected function validateVCard(&$data, &$modified)
    {
        if (is_resource($data)) {
            $data = (string) stream_get_contents($data);
        }

        if ($this->server->getHTTPPrefer()['handling'] !== 'strict') {
            $data = $this->repaired($data);
            $modified = true;

            return;
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

    private function repaired(string $data): string
    {
        if (str_starts_with($data, '[')) {
            try {
                $data = Reader::readJson($data)->serialize();
            } catch (ParseException $exception) {
                throw new UnsupportedMediaType('This resource only supports valid vCard or jCard data.', 0, $exception);
            }
        }

        $unreadable = [];
        $card = $this->readSettingAside($data, $unreadable);

        if (! $card instanceof VCard) {
            throw new UnsupportedMediaType('This collection can only hold vCards.');
        }

        $card->validate(Node::PROFILE_CARDDAV | Node::REPAIR);

        foreach ($unreadable as $line) {
            $card->add(self::UNREADABLE_LINE, $line);
        }

        return $card->serialize();
    }

    /**
     * The parser names the first line it cannot read, so that line and its continuation are set
     * aside and the rest is read again, until nothing is left that it refuses. Blank lines go first:
     * the parser skips them without always counting them, which would name the wrong line.
     *
     * @param  list<string>  $unreadable
     */
    private function readSettingAside(string $data, array &$unreadable): Document
    {
        $lines = array_values(array_filter(preg_split('/\r?\n/', $data) ?: [], fn (string $line): bool => $line !== ''));

        for ($attempt = 0; ; $attempt++) {
            try {
                return Reader::read(implode("\r\n", $lines), Reader::OPTION_FORGIVING);
            } catch (ParseException $exception) {
                if ($attempt === self::MOST_UNREADABLE_LINES || preg_match('/Line starting at (\d+)/', $exception->getMessage(), $match) !== 1) {
                    throw new UnsupportedMediaType('This resource only supports valid vCard or jCard data.', 0, $exception);
                }

                $start = (int) $match[1] - 1;
                $end = $start + 1;

                while (isset($lines[$end]) && in_array(substr($lines[$end], 0, 1), [' ', "\t"], true)) {
                    $end++;
                }

                $unreadable[] = implode("\n", array_slice($lines, $start, $end - $start));
                array_splice($lines, $start, $end - $start);
            }
        }
    }

    private function withTheUnreadableLinesAsSent(string $card): string
    {
        $document = Reader::read($card, Reader::OPTION_FORGIVING);
        $sent = array_map(fn (Property $property): string => (string) $property->getValue(), iterator_to_array($document->select(self::UNREADABLE_LINE), false));

        if ($sent === []) {
            return $card;
        }

        $lines = [];
        $continuing = false;

        foreach (explode("\r\n", $card) as $line) {
            if ($continuing && in_array(substr($line, 0, 1), [' ', "\t"], true)) {
                continue;
            }

            $continuing = preg_match('/^'.self::UNREADABLE_LINE.'[;:]/i', $line) === 1;
            $lines[] = $continuing ? str_replace("\n", "\r\n", (string) array_shift($sent)) : $line;
        }

        return implode("\r\n", $lines);
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

    /**
     * A group card carries both forms of its kind and its members. Converting to 3.0 writes the
     * standard form into the Apple one, so the Apple lines already there are dropped first rather
     * than sent twice.
     */
    private function withoutTheAppleGroupForm(string $data): string
    {
        $card = Reader::read($data);

        $kinds = $card instanceof VCard ? $card->select('KIND') : [];

        if (! $card instanceof VCard || $kinds === [] || mb_strtolower((string) reset($kinds)) !== 'group') {
            return $data;
        }

        $card->remove('X-ADDRESSBOOKSERVER-KIND');
        $card->remove('X-ADDRESSBOOKSERVER-MEMBER');

        return $card->serialize();
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
