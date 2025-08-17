<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ImportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCalendarResource;
use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDate;
use App\Models\ContactTask;
use App\Services\BaseService;
use App\Traits\DAVFormat;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;
use Sabre\VObject\Component\VCalendar;

class ImportVCalendar extends BaseService implements ServiceInterface
{
    use DAVFormat;

    public ?array $data = null;

    public bool $external = false;

    /** @var Collection<array-key,ImportVCalendarResource> */
    private static ?Collection $importers = null;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_important_date_id' => 'nullable|integer|exists:contact_important_dates,id',
            'contact_task_id' => 'nullable|integer|exists:contact_tasks,id',
            'entry' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) && ! is_resource($value) && ! $value instanceof VCalendar) {
                        $fail($attribute.' must be a string, a resource, or a VCalendar object.');
                    }
                },
            ],
            'etag' => 'nullable|string',
            'uri' => 'nullable|string',
            'external' => 'nullable|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Import one VCalendar.
     */
    public function execute(array $data): ?array
    {
        $this->data = $data;

        $this->validateRules($data);

        $this->external = Arr::get($data, 'external', false);

        return $this->process($data);
    }

    #[\Override]
    public function validateRules(array $data): bool
    {
        if (! parent::validateRules($data)) {
            return false;
        }

        if (isset($data['contact_important_date_id'])) {
            $obj = ContactImportantDate::find($data['contact_important_date_id']);
            if ($obj->contact->vault_id !== $data['vault_id']) {
                throw new ModelNotFoundException;
            }
        } elseif (isset($data['contact_task_id'])) {
            $obj = ContactTask::find($data['contact_task_id']);
            if ($obj->contact->vault_id !== $data['vault_id']) {
                throw new ModelNotFoundException;
            }
        }

        return true;
    }

    /**
     * Process data importation.
     *
     * @return array<string,mixed>
     */
    private function process(array $data): ?array
    {
        /**
         * @var VCalendar|null $entry
         * @var string $vcalendar
         */
        [$entry, $vcalendar] = $this->getEntry($data);

        if ($entry === null) {
            return null;
        }

        return $this->processEntry($entry, $vcalendar);
    }

    /**
     * Read the entry and return a VCalendar object.
     */
    private function getEntry(array $data): array
    {
        $entry = $vcalendar = $data['entry'];

        if (! $entry instanceof VCalendar) {
            $entry = (new ReadVObject)->execute([
                'entry' => $entry,
            ]);

            if ($entry === null) {
                return [null, $vcalendar];
            }
        }

        if ($vcalendar instanceof VCalendar) {
            $vcalendar = $entry->serialize();
        }

        return [$entry, $vcalendar];
    }

    /**
     * Process entry importation.
     *
     * @return array<string,mixed>
     */
    private function processEntry(VCalendar $entry, string $vcalendar): ?array
    {
        if (! $this->canImportCurrentEntry($entry)) {
            return null;
        }

        $result = $this->importEntry($entry);

        if ($result === null) {
            return null;
        }

        // Save vcalendar content
        $result = $result->withoutTimestamps(function () use ($result, $vcalendar): mixed {
            $result->vcalendar = $vcalendar;
            $result->save();

            return $result;
        });

        return [
            'id' => $result->id,
            'entry' => $result,
        ];
    }

    /**
     * Check whether an importer can import this card.
     */
    private function canImportCurrentEntry(VCalendar $entry): bool
    {
        $importers = $this->importers()
            ->filter(fn (ImportVCalendarResource $importer): bool => $importer->handle($entry));

        if ($importers->isEmpty()) {
            return false;
        }

        foreach ($importers as $importer) {
            if (! $importer->can($entry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create the object matching the current entry.
     */
    private function importEntry(VCalendar $entry): ?VCalendarResource
    {
        $result = null;

        $importers = $this->importers()
            ->filter(fn (ImportVCalendarResource $importer): bool => $importer->handle($entry));

        foreach ($importers as $importer) {
            $result = $importer->import($entry, $result);
        }

        return $result;
    }

    /**
     * Get importers instance.
     *
     * @return Collection<array-key,ImportVCalendarResource>
     */
    private function importers(): Collection
    {
        if (self::$importers === null) {
            self::$importers = collect(subClasses(ImportVCalendarResource::class))
                ->sortBy(fn (ReflectionClass $importer): int => Order::get($importer))
                ->map(fn (ReflectionClass $importer): ImportVCalendarResource => $importer->newInstance());
        }

        return self::$importers
            ->map(fn (ImportVCalendarResource $importer): ImportVCalendarResource => $importer->setContext($this));
    }
}
