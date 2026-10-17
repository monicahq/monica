<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContactFieldRowsViewModel
{
    public const int PER_PAGE = 10;

    /** @var LengthAwarePaginator<int, FieldValueRow>|null */
    private ?LengthAwarePaginator $rows = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly Field $field,
        private readonly int $page = 1,
    ) {}

    public function field(): Field
    {
        return $this->field;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function readsAsNotes(): bool
    {
        if (! $this->field->type->isRepeater()) {
            return false;
        }

        $answers = $this->children();

        return $answers->count() === 1 && $answers->first()?->type === FieldTypeEnum::Textarea;
    }

    /** @return LengthAwarePaginator<int, FieldValueRow> */
    public function rows(): LengthAwarePaginator
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $query = $this->contact
            ->fieldValueRows()
            ->where('field_id', $this->field->id)
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])]);

        $query = $this->readsAsNotes()
            ? $query->orderByDesc('created_at')->orderByDesc('id')
            : $query->orderBy('position')->orderBy('id');

        return $this->rows = $query
            ->paginate(self::PER_PAGE, ['*'], 'page', $this->page)
            ->withPath(route('contacts.fieldRows.index', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'field' => $this->field,
            ]));
    }

    /** @return array<int, array{id: int, fields: array<int, array<string, mixed>>}> */
    public function shapedRows(): array
    {
        return array_map(fn (FieldValueRow $row): array => $this->shapeRow($row), $this->rows()->items());
    }

    /** @return array{id: int, fields: array<int, array<string, mixed>>} */
    public function shapeRow(FieldValueRow $row): array
    {
        return [
            'id' => $row->id,
            'fields' => $this->children()
                ->map(fn (Field $child): array => $this->answerOn($row, $child))
                ->values()
                ->all(),
        ];
    }

    /** @return array<int, array{id: int, shown: array<int, string>, hidden: array<int, string>, addedLine: string}> */
    public function noteEntries(): array
    {
        $entries = [];

        foreach ($this->rows()->items() as $row) {
            $entry = $this->shapeNote($row);

            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /** @return array{id: int, shown: array<int, string>, hidden: array<int, string>, addedLine: string}|null */
    public function shapeNote(FieldValueRow $row): ?array
    {
        $text = trim((string) $row->values->first()?->value_text);

        if ($text === '') {
            return null;
        }

        $paragraphs = preg_split('/\R\s*\R/', $text) ?: [$text];

        return [
            'id' => $row->id,
            'shown' => array_slice($paragraphs, 0, 2),
            'hidden' => array_slice($paragraphs, 2),
            'addedLine' => __('Added :date', [
                'date' => DateFormatEnum::write($row->created_at->setTimezone($this->user->timezone), $this->dateFormat()),
            ]),
        ];
    }

    public function nextPageUrl(): ?string
    {
        return $this->rows()->nextPageUrl();
    }

    public function rowsId(): string
    {
        return "field-{$this->field->id}-rows";
    }

    public function footerId(): string
    {
        return "field-{$this->field->id}-more";
    }

    public function showsEnd(): bool
    {
        return $this->page > 1;
    }

    public function count(): int
    {
        return count($this->rows()->items());
    }

    /** @return array{rowsId: string, footerId: string, nextPageUrl: string|null, showsEnd: bool, count: int} */
    public function more(): array
    {
        return [
            'rowsId' => $this->rowsId(),
            'footerId' => $this->footerId(),
            'nextPageUrl' => $this->nextPageUrl(),
            'showsEnd' => $this->showsEnd(),
            'count' => $this->count(),
        ];
    }

    /** @return Collection<int, Field> */
    private function children(): Collection
    {
        return $this->field->children->filter(fn (Field $child): bool => $child->type->storesValue());
    }

    /** @return array<string, mixed> */
    private function answerOn(FieldValueRow $row, Field $child): array
    {
        $value = $row->values->firstWhere('field_id', $child->id);

        return [
            'id' => $child->id,
            'label' => $child->label,
            'help' => $child->help_text,
            'field' => $child,
            'rawValue' => $value,
            'value' => $value === null ? null : $this->formatter()->format($value),
        ];
    }

    private function formatter(): FieldValueFormatter
    {
        return once(fn (): FieldValueFormatter => new FieldValueFormatter($this->user));
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
