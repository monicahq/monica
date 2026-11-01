<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\SourceFormatVisitor;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Helpers\FlagState;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Write one item of an import into its vault through the actions the screens use, and record what
 * became of it. An item that cannot be written is left out with its reason, and only a crossed limit
 * stops the run, for the rollback to undo.
 */
class WriteAccountImportItem implements SourceFormatVisitor
{
    use WritesMonicaFourItems;
    use WritesMonicaItems;

    private const array KINDS_KEEPING_THEIR_CREATION_DATE = [
        'file', 'contact', 'entity', 'reminder', 'life_event', 'call', 'conversation',
        'pet', 'activity', 'relationship', 'group', 'journal_entry',
    ];

    private ?Model $record = null;

    private AccountImportOutcomeEnum $outcome = AccountImportOutcomeEnum::Imported;

    private ?AccountImportReasonEnum $reason = null;

    private int $valuesNotWritten = 0;

    /** @var array<string, int> */
    private array $written = [];

    private ?FieldValueRow $lastRow = null;

    /** @var array<string, mixed>|null */
    private ?array $keptPayload = null;

    /** @var list<string> */
    private array $temporaryPaths = [];

    public function __construct(
        private readonly User $user,
        private readonly AccountImport $import,
        private readonly AccountImportItem $item,
    ) {}

    public function execute(): void
    {
        if ($this->item->outcome !== null) {
            return;
        }

        try {
            DB::transaction(function (): void {
                $this->write();

                if (in_array($this->item->kind, self::KINDS_KEEPING_THEIR_CREATION_DATE, true)) {
                    $this->keepTheCreationDate($this->record, $this->payload('created_at'));
                }

                $this->record();
            });
        } catch (AuthorizationException|NotEnoughStorageLeft $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->item->refresh();
            $this->record = null;
            $this->keptPayload = null;
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);
            $this->record();
        } finally {
            foreach ($this->temporaryPaths as $temporaryPath) {
                @unlink($temporaryPath);
            }
        }
    }

    public function visitMonica(): void
    {
        $this->writeAMonicaItem();
    }

    public function visitMonicaFour(): void
    {
        $this->writeAMonicaFourItem();
    }

    private function write(): void
    {
        if (! $this->import->source_format instanceof AccountImportSourceEnum) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        $this->import->source_format->accept($this);
    }

    private function writeAFile(): void
    {
        if (! FlagState::isOn('file-upload', $this->user->account)) {
            $this->leaveOut(AccountImportReasonEnum::FilesNotOffered);

            return;
        }

        $this->record = new CreateFile(
            user: $this->user,
            upload: $this->upload(
                Storage::disk($this->import->disk),
                (string) $this->payload('path'),
                (string) $this->payload('original_filename'),
                (string) $this->payload('mime_type'),
            ),
            vault: $this->vault(),
        )->execute();
    }

    private function writeIntoMe(Contact $me, Closure $write): void
    {
        $valuesOfMe = fn (): array => FieldValue::query()->where('valuable_type', $me->getMorphClass())->where('valuable_id', $me->id)->pluck('id')->all();
        $rowsOfMe = fn (): array => FieldValueRow::query()->where('valuable_type', $me->getMorphClass())->where('valuable_id', $me->id)->pluck('id')->all();

        $valuesBefore = $valuesOfMe();
        $rowsBefore = $rowsOfMe();
        $avatarBefore = $me->avatar_file_id;

        $write();

        $this->removeTheRowsMeAlreadyHolds($me, $rowsBefore);
        $me->refresh();

        $this->outcome = AccountImportOutcomeEnum::MergedIntoMe;
        $this->keptPayload = [
            ...($this->keptPayload ?? []),
            'values' => array_values(array_diff($valuesOfMe(), $valuesBefore)),
            'rows' => array_values(array_diff($rowsOfMe(), $rowsBefore)),
            'avatar_file' => $me->avatar_file_id === $avatarBefore ? null : $me->avatar_file_id,
        ];
    }

    /** @param list<int> $rowsBefore */
    private function removeTheRowsMeAlreadyHolds(Contact $me, array $rowsBefore): void
    {
        $rows = FieldValueRow::query()
            ->where('valuable_type', $me->getMorphClass())
            ->where('valuable_id', $me->id)
            ->with('values')
            ->get();

        $reading = fn (FieldValueRow $row): string => $row->field_id.':'.$row->values
            ->sortBy('field_id')
            ->map(fn (FieldValue $value): string => $value->field_id.'='.json_encode($value->value))
            ->implode(',');

        $held = $rows->whereIn('id', $rowsBefore)->map($reading)->flip();

        foreach ($rows->whereNotIn('id', $rowsBefore) as $row) {
            if ($held->has($reading($row))) {
                new DestroyFieldValueRow(user: $this->user, row: $row, writesToTheJournal: false)->execute();
            }
        }
    }

    private function joinsMe(mixed $sourceUuid): bool
    {
        return AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('kind', 'me')
            ->where('source_uuid', $sourceUuid)
            ->exists();
    }

    private function meOfTheVault(): Contact
    {
        return Contact::query()
            ->where('vault_id', $this->vault()->id)
            ->where('is_me', true)
            ->firstOrFail();
    }

    private function isWrittenIntoMe(mixed $sourceUuid): bool
    {
        return AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('kind', 'contact')
            ->where('source_uuid', $sourceUuid)
            ->where('outcome', AccountImportOutcomeEnum::MergedIntoMe->value)
            ->exists();
    }

    private function meAlreadyHolds(Contact|Entity|LifeEvent $record, Field $field): bool
    {
        if (! $record instanceof Contact || ! $record->is_me || $field->parent_field_id !== null) {
            return false;
        }

        return FieldValue::query()
            ->where('valuable_type', $record->getMorphClass())
            ->where('valuable_id', $record->id)
            ->where('field_id', $field->id)
            ->exists();
    }

    private function archive(): void
    {
        $entity = $this->entityOf($this->payload('entity'));

        if ($entity instanceof Entity) {
            $entity->is_archived = true;
            $entity->save();

            new DeactivateReminders(user: $this->user, subject: $entity, reason: ReminderInactiveReasonEnum::RecordArchived)->execute();

            return;
        }

        $contact = $this->contactOf($this->payload('contact'));

        if (! $contact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        if ($contact->is_me) {
            return;
        }

        new ArchiveContact(user: $this->user, contact: $contact)->execute();
    }

    private function putTheAvatar(Contact $contact, ?File $avatar): void
    {
        if (! $avatar instanceof File || $contact->avatar_file_id !== null) {
            return;
        }

        try {
            new UpdateContactAvatar(
                user: $this->user,
                vault: $this->vault(),
                contact: $contact,
                upload: $this->upload($avatar->disk(), $avatar->path, $avatar->original_name, $avatar->mime_type),
            )->execute();
        } catch (NotEnoughStorageLeft $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Left out the photo an import could not write.', [
                'import' => $this->import->id,
                'item' => $this->item->id,
                'exception' => $exception->getMessage(),
            ]);

            $this->valuesNotWritten++;
        }
    }

    private function itemOf(?Field $field, mixed $key): ?ValueListItem
    {
        if (! $field instanceof Field || $field->value_list_id === null || ! is_string($key)) {
            return null;
        }

        return ValueListItem::query()->where('value_list_id', $field->value_list_id)->where('key', $key)->first()
            ?? ValueListItem::query()->where('value_list_id', $field->value_list_id)->whereNull('key')->where('label', $key)->first();
    }

    private function nextOccurrence(CarbonImmutable $date, ReminderCadenceEnum $cadence, int $interval): CarbonImmutable
    {
        while ($date->isBefore(today())) {
            $date = match ($cadence) {
                ReminderCadenceEnum::Weekly => $date->addWeeks($interval),
                ReminderCadenceEnum::Monthly => $date->addMonthsNoOverflow($interval),
                default => $date->addYearsNoOverflow($interval),
            };
        }

        return $date;
    }

    private function contactOf(mixed $sourceUuid): ?Contact
    {
        if ($this->isWrittenIntoMe($sourceUuid)) {
            return $this->meOfTheVault();
        }

        $id = $this->recordIdOf('contact', $sourceUuid);

        return $id === null ? null : Contact::query()->find($id);
    }

    private function entityOf(mixed $sourceUuid): ?Entity
    {
        $id = $this->recordIdOf('entity', $sourceUuid);

        return $id === null ? null : Entity::query()->find($id);
    }

    private function fileOf(mixed $sourceUuid): ?File
    {
        $id = $this->recordIdOf('file', $sourceUuid);

        return $id === null ? null : File::query()->find($id);
    }

    private function recordIdOf(string $kind, mixed $sourceUuid): ?int
    {
        if (! is_string($sourceUuid) || $sourceUuid === '') {
            return null;
        }

        $id = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('kind', $kind)
            ->where('source_uuid', $sourceUuid)
            ->value('record_id');

        return $id === null ? null : (int) $id;
    }

    private function keepTheCreationDate(?Model $record, mixed $createdAt): void
    {
        if (! $record instanceof Model || ! is_string($createdAt)) {
            return;
        }

        $record->setAttribute('created_at', CarbonImmutable::parse($createdAt));
        $record->saveQuietly();
    }

    private function upload(Filesystem $disk, string $path, string $name, string $mimeType): UploadedFile
    {
        $temporaryPath = (string) tempnam(sys_get_temp_dir(), 'import-');
        $this->temporaryPaths[] = $temporaryPath;

        $source = $disk->readStream($path);
        $target = fopen($temporaryPath, 'wb');

        if (is_resource($source) && is_resource($target)) {
            stream_copy_to_stream($source, $target);
        }

        if (is_resource($source)) {
            fclose($source);
        }

        if (is_resource($target)) {
            fclose($target);
        }

        return new UploadedFile($temporaryPath, $name, $mimeType, test: true);
    }

    private function vault(): Vault
    {
        return Vault::query()->findOrFail($this->recordIdOf('vault', $this->item->vault_source_uuid) ?? $this->import->vault_id);
    }

    /** @return array<string, mixed> */
    private function reference(string $list): array
    {
        return (array) ($this->import->source_references[$list] ?? []);
    }

    private function payload(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->item->payload ?? [], $key, $default);
    }

    private function text(mixed $value): ?string
    {
        $text = is_scalar($value) ? mb_trim((string) $value) : '';

        return $text === '' ? null : $text;
    }

    private function shortText(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : mb_substr($text, 0, 255);
    }

    private function leaveOut(AccountImportReasonEnum $reason): void
    {
        $this->outcome = AccountImportOutcomeEnum::LeftOut;
        $this->reason = $reason;
    }

    private function record(): void
    {
        $this->item->outcome = $this->outcome;
        $this->item->reason = $this->reason?->value;
        $this->item->record_type = $this->record?->getMorphClass();
        $this->item->record_id = $this->record?->getKey();

        if ($this->outcome !== AccountImportOutcomeEnum::LeftOut) {
            $this->item->payload = $this->written === []
                ? $this->keptPayload
                : [...($this->keptPayload ?? []), 'written' => $this->written];
        }

        $this->item->save();
    }
}
