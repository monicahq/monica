<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use App\Traits\DAVFormat;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use ReflectionClass;
use Sabre\VObject\Component\VCard;

class ImportVCard extends BaseService implements ServiceInterface
{
    use DAVFormat;

    /** @var string */
    public const BEHAVIOUR_ADD = 'behaviour_add';

    /** @var string */
    public const BEHAVIOUR_REPLACE = 'behaviour_replace';

    /** @var string */
    protected const ERROR_PARSER = 'ERROR_PARSER';

    /** @var string */
    protected const ERROR_CONTACT_EXIST = 'ERROR_CONTACT_EXIST';

    /** @var string */
    protected const ERROR_CARD_NOT_IMPORTABLE = 'ERROR_CARD_NOT_IMPORTABLE';

    /**
     * Error results.
     *
     * @var array<string,string>
     */
    protected static array $errorResults = [
        self::ERROR_PARSER => 'import_vcard_parse_error',
        self::ERROR_CONTACT_EXIST => 'import_vcard_contact_exist',
        self::ERROR_CARD_NOT_IMPORTABLE => 'import_vcard_not_importable',
    ];

    /**
     * Valids value for frequency type.
     *
     * @var array<string>
     */
    public static array $behaviourTypes = [
        self::BEHAVIOUR_ADD,
        self::BEHAVIOUR_REPLACE,
    ];

    public bool $external = false;

    public ?array $data = null;

    /** @var Collection<array-key,ImportVCardResource> */
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
            'contact_id' => 'nullable|uuid|exists:contacts,id',
            'entry' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) && ! is_resource($value) && ! $value instanceof VCard) {
                        $fail($attribute.' must be a string, a resource, or a VCard object.');
                    }
                },
            ],
            'behaviour' => [
                'required',
                Rule::in(self::$behaviourTypes),
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
     * Import one VCard.
     */
    public function execute(array $data): array
    {
        $this->data = $data;

        $this->validateRules($data);

        $this->external = Arr::get($data, 'external', false);

        return $this->process($data);
    }

    /**
     * Process data importation.
     *
     * @return array<string,mixed>
     */
    private function process(array $data): array
    {
        /**
         * @var VCard|null $entry
         * @var string $vcard
         */
        [$entry, $vcard] = $this->getEntry($data);

        if ($entry === null) {
            return [
                'error' => self::ERROR_PARSER,
                'reason' => static::$errorResults[self::ERROR_PARSER],
            ];
        }

        return $this->processEntry($entry, $vcard);
    }

    /**
     * Read the entry and return a VCard object.
     */
    private function getEntry(array $data): array
    {
        $entry = $vcard = $data['entry'];

        if (! $entry instanceof VCard) {
            $entry = (new ReadVObject)->execute([
                'entry' => $entry,
            ]);

            if ($entry === null) {
                return [null, $vcard];
            }
        }

        if ($vcard instanceof VCard) {
            $vcard = $entry->serialize();
        }

        return [$entry, $vcard];
    }

    /**
     * Process entry importation.
     *
     * @return array<string,mixed>
     */
    private function processEntry(VCard $entry, string $vcard): array
    {
        if (! $this->canImportCurrentEntry($entry)) {
            return [
                'error' => self::ERROR_CARD_NOT_IMPORTABLE,
                'reason' => static::$errorResults[self::ERROR_CARD_NOT_IMPORTABLE],
            ];
        }

        return $this->processEntryCard($entry, $vcard);
    }

    /**
     * Process entry importation.
     *
     * @return array<string,mixed>
     */
    private function processEntryCard(VCard $entry, string $vcard): array
    {
        $result = $this->importEntry($entry);

        if ($result === null) {
            return [
                'error' => self::ERROR_CARD_NOT_IMPORTABLE,
                'reason' => static::$errorResults[self::ERROR_CARD_NOT_IMPORTABLE],
            ];
        }

        // Save vcard content
        $result = $result->withoutTimestamps(function () use ($result, $vcard): mixed {
            $result->vcard = $vcard;
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
    private function canImportCurrentEntry(VCard $entry): bool
    {
        $importers = $this->importers()
            ->filter(fn (ImportVCardResource $importer): bool => $importer->handle($entry));

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
    private function importEntry(VCard $entry): ?VCardResource
    {
        $result = null;

        $importers = $this->importers()
            ->filter(fn (ImportVCardResource $importer): bool => $importer->handle($entry));

        foreach ($importers as $importer) {
            $result = $importer->import($entry, $result);
        }

        return $result;
    }

    /**
     * Get importers instance.
     *
     * @return Collection<array-key,ImportVCardResource>
     */
    private function importers(): Collection
    {
        if (self::$importers === null) {
            self::$importers = collect(subClasses(ImportVCardResource::class))
                ->sortBy(fn (ReflectionClass $importer): int => Order::get($importer))
                ->map(fn (ReflectionClass $importer): ImportVCardResource => $importer->newInstance());
        }

        return self::$importers
            ->map(fn (ImportVCardResource $importer): ImportVCardResource => $importer->setContext($this));
    }
}
