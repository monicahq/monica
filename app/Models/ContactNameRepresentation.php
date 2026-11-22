<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactNameKindEnum;
use App\Enums\NameOrderEnum;
use App\Helpers\LocaleCollation;
use App\Helpers\NameFiling;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\ContactNameRepresentationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ContactNameRepresentation
 *
 * One way to write or say the current name of a contact: the name in its own script, a
 * transliteration into another, or how it is pronounced. 김민지, Kim Min-ji and 金敏智 are three
 * representations of one name, not three names, and a contact holds as many as somebody records.
 *
 * One of them is the display name, pointed at by the contact, and only that one decides how the
 * contact is listed, titled, addressed and drawn. The contact keeps a copy of its parts in its own
 * columns, written here and nowhere else, so a list of thousands reads names without a join. Every
 * representation is searchable, and every one is compared when duplicates are looked for.
 *
 * Nothing is generated: a transliteration exists because somebody wrote it down. A representation
 * holds a formatted name or at least one part; the actions refuse one holding neither.
 *
 * @property int $id
 * @property string $uuid
 * @property int $contact_id
 * @property ContactNameKindEnum $kind
 * @property string|null $language_tag
 * @property string|null $script_code
 * @property string|null $phonetic_system
 * @property string|null $formatted_name
 * @property string|null $prefix
 * @property string|null $given_name
 * @property string|null $additional_name
 * @property string|null $family_name
 * @property string|null $suffix
 * @property NameOrderEnum|null $name_order
 * @property string $sort_name
 * @property string $search_key
 * @property string $match_key
 * @property string|null $vcard_alt_id
 * @property string|null $vcard_property_id
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Contact $contact
 */
#[Fillable([
    'contact_id',
    'kind',
    'language_tag',
    'script_code',
    'phonetic_system',
    'formatted_name',
    'prefix',
    'given_name',
    'additional_name',
    'family_name',
    'suffix',
    'name_order',
    'vcard_alt_id',
    'vcard_property_id',
    'position',
])]
class ContactNameRepresentation extends Model
{
    /** @use HasFactory<ContactNameRepresentationFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Write the keys the lists, the search and the duplicate detection read, in the account's
     * language, and keep the contact in step once the row is written or deleted: its copy of the
     * display name when this is the one shown, its place in the search otherwise, and its place in
     * the lists when its language files a name under a pronunciation.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (ContactNameRepresentation $representation): void {
            $representation->writeKeys();
        });

        static::saved(function (ContactNameRepresentation $representation): void {
            $representation->keepTheContactInStep();
        });

        static::deleting(function (ContactNameRepresentation $representation): void {
            $contact = $representation->contact;

            if ($contact->display_name_representation_id === $representation->id) {
                $contact->display_name_representation_id = null;
                $contact->save();
            }
        });

        static::deleted(function (ContactNameRepresentation $representation): void {
            $contact = $representation->contact;
            $contact->unsetRelation('nameRepresentations');

            if (NameFiling::phoneticScripts($contact->account->locale()) !== []) {
                $contact->save();
            }

            Contact::sendToSearch(Contact::query()->whereKey($contact->id));
            $contact->resendTheRecordsNamingIt();
        });
    }

    /**
     * Get the contact whose name this is.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The text this representation shows: the formatted name when there is one, the parts in their
     * order otherwise, or an empty string for a representation holding nothing.
     */
    public function displayText(): string
    {
        $formatted = mb_trim((string) $this->formatted_name);

        if ($formatted !== '') {
            return $formatted;
        }

        return $this->composedText();
    }

    /**
     * The parts put together in the order this name reads in. The additional name sits with the
     * given name, since it is one.
     */
    public function composedText(): string
    {
        $given = mb_trim("{$this->given_name} {$this->additional_name}");
        $name = ($this->name_order ?? NameOrderEnum::GivenFirst)->format($given, (string) $this->family_name);

        return mb_trim(implode(' ', array_filter([$this->prefix, $name, $this->suffix])));
    }

    public function isDisplayed(): bool
    {
        return $this->contact->display_name_representation_id === $this->id;
    }

    /**
     * @return array{kind: string, language: string|null, script: string|null, phonetic_system: string|null, formatted_name: string|null, prefix: string|null, given_name: string|null, additional_name: string|null, family_name: string|null, suffix: string|null, name_order: string|null}
     */
    public function toComparable(): array
    {
        return [
            'kind' => $this->kind->value,
            'language' => $this->language_tag,
            'script' => $this->script_code,
            'phonetic_system' => $this->phonetic_system,
            'formatted_name' => $this->formatted_name,
            'prefix' => $this->prefix,
            'given_name' => $this->given_name,
            'additional_name' => $this->additional_name,
            'family_name' => $this->family_name,
            'suffix' => $this->suffix,
            'name_order' => $this->name_order?->value,
        ];
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'kind' => ContactNameKindEnum::class,
            'name_order' => NameOrderEnum::class,
            'position' => 'integer',
        ];
    }

    /**
     * Build the sort, search and duplicate keys from the name as it now stands.
     */
    public function writeKeys(): void
    {
        $locale = $this->contact->account->locale();

        $sortName = collect([$this->family_name, $this->given_name, $this->additional_name])
            ->filter()
            ->implode(' ');

        if ($sortName === '') {
            $sortName = (string) $this->formatted_name;
        }

        $searchable = collect([$this->formatted_name, $this->composedText()])
            ->map(fn (?string $text): string => mb_trim((string) $text))
            ->filter()
            ->unique()
            ->implode(' ');

        $this->sort_name = mb_substr(mb_strtolower(mb_trim($sortName)), 0, 511);
        $this->search_key = LocaleCollation::foldForSearch($searchable, $locale);
        $this->match_key = LocaleCollation::matchKey(mb_trim((string) ($this->family_name ?? $this->given_name ?? $this->formatted_name)), $locale);
    }

    private function keepTheContactInStep(): void
    {
        $contact = $this->contact;
        $contact->unsetRelation('nameRepresentations');

        if ($contact->display_name_representation_id === $this->id) {
            $contact->copyDisplayName($this);
            $contact->save();

            return;
        }

        if (NameFiling::phoneticScripts($contact->account->locale()) !== []) {
            $contact->save();
        }

        Contact::sendToSearch(Contact::query()->whereKey($contact->id));
        $contact->resendTheRecordsNamingIt();
    }
}
