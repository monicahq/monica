<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueFormatter;
use App\Helpers\LocationTree;
use App\Helpers\PossessionTimeline;
use App\Helpers\PossessionValueFormatter;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The page of one possession: its type, where it is kept, its tags, and its Overview, which gives its
 * description, the values of its type's fields, its declared value and the facts about it. The
 * header and the list of sections are shared with the Transactions and Insurance sections; the other
 * sections are listed but come in a later version.
 */
class PossessionViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
    ) {
        $this->possession->loadMissing([
            'vault',
            'possessionType.groups.fields',
            'possessionType.standaloneFields',
            'values',
            'tags',
            'location',
            'transactions',
            'insuranceRecords',
            'maintenanceRecords',
            'valuations',
        ]);
    }

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->possession->vault;
    }

    public function name(): string
    {
        return $this->possession->name;
    }

    /** @return array{isFeatured: bool, type: string, icon: string, colour: string, tint: string, place: ?array{name: string, holder: ?string}, tags: list<array{id: string, name: string, colour: string, tint: string, removeUrl: string}>} */
    public function header(): array
    {
        $type = $this->possession->possessionType;

        return [
            'isFeatured' => $this->possession->is_featured,
            'type' => $type->name,
            'icon' => $type->icon ?? Section::DEFAULT_ICON,
            'colour' => $type->colour,
            'tint' => VaultColourEnum::tintFor($type->colour),
            'place' => $this->place(),
            'tags' => $this->possession->tags->map(fn (PossessionTag $tag): array => [
                'id' => $tag->uuid,
                'name' => $tag->name,
                'colour' => $tag->colour,
                'tint' => VaultColourEnum::tintFor($tag->colour),
                'removeUrl' => route('possessions.tags.destroy', [$this->possession->vault, $this->possession, $tag]),
            ])->values()->all(),
        ];
    }

    /**
     * Every tag of the account, for the panel that puts them on the possession. The count of other
     * possessions carrying a tag spans every vault, since tags belong to the account.
     *
     * @return list<array{id: string, name: string, colour: string, carried: bool, others: string, url: string}>
     */
    public function tagOptions(): array
    {
        $carried = $this->possession->tags->pluck('id')->all();

        return $this->user->account->possessionTags()
            ->withCount('possessions')
            ->orderBy('name')
            ->get()
            ->map(function (PossessionTag $tag) use ($carried): array {
                $isCarried = in_array($tag->id, $carried, true);
                $others = (int) $tag->possessions_count - ($isCarried ? 1 : 0);

                return [
                    'id' => $tag->uuid,
                    'name' => $tag->name,
                    'colour' => $tag->colour,
                    'carried' => $isCarried,
                    'others' => $others === 0 ? __('Unused') : trans_choice(':count other|:count others', $others),
                    'url' => route('possessions.tags.update', [$this->possession->vault, $this->possession, $tag]),
                ];
            })
            ->values()
            ->all();
    }

    public function tagCount(): string
    {
        $count = $this->possession->tags->count();

        if ($count === 0) {
            return __('No tags on this possession yet');
        }

        return trans_choice(':count tag on this possession|:count tags on this possession', $count);
    }

    /** @return array{colour: string, name: string} */
    public function nextTagColour(): array
    {
        $colour = $this->user->account->nextPossessionTagColour();

        return ['colour' => $colour->value, 'name' => __($colour->label())];
    }

    public function createTagUrl(): string
    {
        return route('possessions.tags.create', [$this->possession->vault, $this->possession]);
    }

    public function manageTagsUrl(): string
    {
        return route('settings.possessionTags.index');
    }

    public function description(): ?string
    {
        return $this->possession->description;
    }

    public function typeName(): string
    {
        return $this->possession->possessionType->name;
    }

    /** @return list<array{title: ?string, fields: list<array{label: string, value: string}>}> */
    public function groups(): array
    {
        $type = $this->possession->possessionType;
        $groups = [];

        foreach ($type->groups as $group) {
            $groups[] = ['title' => $group->name, 'fields' => $this->filled($group->fields->toBase())];
        }

        $groups[] = ['title' => null, 'fields' => $this->filled($type->standaloneFields->toBase())];

        return array_values(array_filter($groups, fn (array $group): bool => $group['fields'] !== []));
    }

    /**
     * What the possession is worth now: its current valuation, else what it would cost to replace.
     */
    public function value(): ?string
    {
        $value = $this->possession->currentValue();

        if ($value === null) {
            return null;
        }

        return new PossessionValueFormatter($this->user)->format($value['amount'], $value['currency']);
    }

    /**
     * Where the current value comes from and how it moved from the valuation before it, when it comes
     * from a valuation. The change is left out when there is none before it or the currencies differ.
     *
     * @return array{source: string, change: ?string}|null
     */
    public function valuation(): ?array
    {
        $current = $this->possession->currentValuation();

        if (! $current instanceof PossessionValuation) {
            return null;
        }

        $replacements = ['type' => __($current->type->label()), 'date' => $this->writeDate($current->valued_on)];
        $source = $current->valuer === null
            ? __(':type, :date', $replacements)
            : __(':type by :valuer, :date', [...$replacements, 'valuer' => $current->valuer]);

        $previous = $this->possession->valuations->get(1);

        if (! $previous instanceof PossessionValuation || $previous->currency !== $current->currency) {
            return ['source' => $source, 'change' => null];
        }

        $difference = bcsub($current->amount, $previous->amount, 2);
        $formatter = new PossessionValueFormatter($this->user);

        return ['source' => $source, 'change' => match (bccomp($difference, '0', 2)) {
            1 => __('Up :amount from the previous estimate', ['amount' => $formatter->formatExact($difference, $current->currency->value)]),
            -1 => __('Down :amount from the previous estimate', ['amount' => $formatter->formatExact(mb_ltrim($difference, '-'), $current->currency->value)]),
            default => __('Unchanged from the previous estimate'),
        }];
    }

    /** @return list<array{label: string, value: string}> */
    public function facts(): array
    {
        $facts = [
            ['label' => __('Type'), 'value' => $this->possession->possessionType->name],
            ['label' => __('Kept in'), 'value' => $this->locationPath()],
            ['label' => __('Status'), 'value' => $this->possession->status->label()],
        ];

        $acquisition = $this->possession->acquisitionTransaction();

        if ($acquisition instanceof PossessionTransaction) {
            $acquired = __($acquisition->type->label());

            if ($acquisition->occurred_on !== null) {
                $acquired = __(':acquisition, :date', [
                    'acquisition' => $acquired,
                    'date' => $this->writeDate($acquisition->occurred_on),
                ]);
            }

            $facts[] = ['label' => __('Acquired'), 'value' => $acquired];
        }

        if ($acquisition?->counterparty !== null) {
            $facts[] = ['label' => __('From'), 'value' => $acquisition->counterparty];
        }

        $insurance = $this->possession->currentInsuranceRecord();

        if ($insurance instanceof PossessionInsuranceRecord) {
            $facts[] = ['label' => __('Insured'), 'value' => __(':insurer, :value', [
                'insurer' => $insurance->insurer,
                'value' => new PossessionValueFormatter($this->user)->formatExact($insurance->insured_value, $insurance->currency->value),
            ])];
        }

        if ($this->possession->quantity > 1) {
            $facts[] = ['label' => __('Quantity'), 'value' => (string) $this->possession->quantity];
        }

        return $facts;
    }

    public function addedLine(): string
    {
        return __('Added on :date', ['date' => $this->writeDate($this->possession->created_at)]);
    }

    public function editedLine(): ?string
    {
        if ($this->possession->updated_at === null) {
            return null;
        }

        return __('Last edited :when', ['when' => $this->possession->updated_at->diffForHumans()]);
    }

    /**
     * The sections of the page, in the design's order. Each icon takes a colour of its own so the
     * sections tell apart at a glance; the label still names them, so the colour carries nothing alone.
     *
     * @return list<array{key: string, label: string, icon: string, colour: string, url: ?string, count: ?int, isCurrent: bool}>
     */
    public function sections(string $current = 'overview'): array
    {
        $sections = [
            ['key' => 'overview', 'label' => __('Overview'), 'icon' => 'info', 'colour' => null, 'url' => $this->url(), 'count' => null],
            ['key' => 'timeline', 'label' => __('Timeline'), 'icon' => 'timeline', 'colour' => null, 'url' => $this->timelineUrl(), 'count' => array_sum(new PossessionTimeline($this->possession)->counts())],
            ['key' => 'transactions', 'label' => __('Transactions'), 'icon' => 'coin', 'colour' => VaultColourEnum::Green, 'url' => $this->transactionsUrl(), 'count' => $this->transactionCount()],
            ['key' => 'insurance', 'label' => __('Insurance'), 'icon' => 'lock', 'colour' => VaultColourEnum::Rust, 'url' => $this->insuranceUrl(), 'count' => $this->possession->insuranceRecords->count()],
            ['key' => 'valuations', 'label' => __('Valuations'), 'icon' => 'activity', 'colour' => VaultColourEnum::Blue, 'url' => $this->valuationsUrl(), 'count' => $this->possession->valuations->count()],
            ['key' => 'provenance', 'label' => __('Provenance'), 'icon' => 'family', 'colour' => VaultColourEnum::Violet, 'url' => $this->provenanceUrl(), 'count' => $this->possession->provenanceEvents->count()],
            ['key' => 'maintenance', 'label' => __('Maintenance'), 'icon' => 'settings', 'colour' => VaultColourEnum::Amber, 'url' => $this->maintenanceUrl(), 'count' => $this->possession->maintenanceRecords->count()],
            ['key' => 'loans', 'label' => __('Loans'), 'icon' => 'relationship', 'colour' => VaultColourEnum::Plum, 'url' => $this->loansUrl(), 'count' => $this->possession->loans->count()],
            ['key' => 'locations', 'label' => __('Locations'), 'icon' => 'place', 'colour' => VaultColourEnum::Teal, 'url' => $this->locationsUrl(), 'count' => $this->possession->locationStays->count()],
            ['key' => 'documents', 'label' => __('Documents'), 'icon' => 'file', 'colour' => VaultColourEnum::Slate, 'url' => null, 'count' => null],
        ];

        return array_map(fn (array $section): array => [
            ...$section,
            'colour' => $section['colour']?->iconForeground() ?? 'text-ink',
            'isCurrent' => $section['key'] === $current,
        ], $sections);
    }

    /** @return list<array{label: string, url?: string|null}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => __('All possessions'), 'url' => $this->listingUrl()],
            ['label' => $this->possession->name],
        ];
    }

    public function isFeatured(): bool
    {
        return $this->possession->is_featured;
    }

    public function url(): string
    {
        return route('possessions.show', [$this->possession->vault, $this->possession]);
    }

    public function timelineUrl(): string
    {
        return route('possessions.timeline.index', [$this->possession->vault, $this->possession]);
    }

    public function transactionsUrl(): string
    {
        return route('possessions.transactions.index', [$this->possession->vault, $this->possession]);
    }

    public function insuranceUrl(): string
    {
        return route('possessions.insuranceRecords.index', [$this->possession->vault, $this->possession]);
    }

    public function valuationsUrl(): string
    {
        return route('possessions.valuations.index', [$this->possession->vault, $this->possession]);
    }

    public function provenanceUrl(): string
    {
        return route('possessions.provenance.index', [$this->possession->vault, $this->possession]);
    }

    public function locationsUrl(): string
    {
        return route('possessions.locationStays.index', [$this->possession->vault, $this->possession]);
    }

    public function loansUrl(): string
    {
        return route('possessions.loans.index', [$this->possession->vault, $this->possession]);
    }

    public function recordValuationUrl(): string
    {
        return "{$this->valuationsUrl()}#new-valuation";
    }

    public function maintenanceUrl(): string
    {
        return route('possessions.maintenanceRecords.index', [$this->possession->vault, $this->possession]);
    }

    public function listingUrl(): string
    {
        return route('possessions.index', $this->possession->vault);
    }

    public function editUrl(): string
    {
        return route('possessions.edit', [$this->possession->vault, $this->possession]);
    }

    public function featuredUrl(): string
    {
        return route('possessions.featured.update', [$this->possession->vault, $this->possession]);
    }

    public function destroyUrl(): string
    {
        return route('possessions.destroy', [$this->possession->vault, $this->possession]);
    }

    /** @return array{name: string, holder: ?string}|null */
    private function place(): ?array
    {
        $location = $this->possession->location;

        if (! $location instanceof Location) {
            return null;
        }

        $topLevel = $location;

        while (($parent = $this->locationTree()->parentOf($topLevel)) instanceof Location) {
            $topLevel = $parent;
        }

        return [
            'name' => $location->name,
            'holder' => $topLevel->is($location) ? null : $topLevel->name,
        ];
    }

    private function locationPath(): string
    {
        $location = $this->possession->location;

        if (! $location instanceof Location) {
            return __('No location');
        }

        $known = $this->locationTree()->find($location->id);

        return $known instanceof Location ? $this->locationTree()->path($known) : $location->name;
    }

    /**
     * @param  Collection<int, PossessionField>  $fields
     * @return list<array{label: string, value: string}>
     */
    private function filled(Collection $fields): array
    {
        $values = $this->possession->values->keyBy('possession_field_id');
        $formatter = new FieldValueFormatter($this->user);
        $filled = [];

        foreach ($fields as $field) {
            $value = $values->get($field->id);

            if (! $value instanceof PossessionFieldValue) {
                continue;
            }

            $value->setRelation('field', $field);
            $written = $formatter->formatPossessionValue($value);

            if ($written === null) {
                continue;
            }

            $filled[] = ['label' => $field->name, 'value' => $written];
        }

        return $filled;
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }

    private function transactionCount(): int
    {
        return $this->possession->transactions->count();
    }

    private function locationTree(): LocationTree
    {
        return once(fn (): LocationTree => LocationTree::forAccount($this->user->account_id));
    }
}
