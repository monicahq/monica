<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ExportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCalendarResource;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactTask;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

class ExportVCalendar extends BaseService implements ServiceInterface
{
    /** @var Collection<array-key,ExportVCalendarResource>|null */
    private static ?Collection $exporters = null;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_important_date_id' => 'required_if:contact_task_id,null|integer|exists:contact_important_dates,id',
            'contact_task_id' => 'required_if:contact_important_date_id,null|integer|exists:contact_tasks,id',
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
        ];
    }

    /**
     * Export one VCalendar.
     */
    public function execute(array $data): VCalendar
    {
        $this->validateRules($data);

        if (isset($data['contact_task_id'])) {
            $obj = ContactTask::find($data['contact_task_id']);
            if ($obj->contact->vault_id !== $data['vault_id']) {
                throw new ModelNotFoundException;
            }
        } elseif (isset($data['contact_important_date_id'])) {
            $obj = ContactImportantDate::find($data['contact_important_date_id']);
            if ($obj->contact->vault_id !== $data['vault_id']) {
                throw new ModelNotFoundException;
            }
        } else {
            throw new ModelNotFoundException;
        }

        $vcalendar = $this->export($obj);

        $obj::withoutTimestamps(function () use ($obj, $vcalendar): void {
            $obj->vcalendar = $vcalendar->serialize();
            $obj->save();
        });

        return $vcalendar;
    }

    private function export(VCalendarResource $obj): VCalendar
    {
        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc5545
        if (! $obj->uuid) {
            $obj->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        if ($obj->vcard) {
            try {
                /** @var VCalendar */
                $vcalendar = Reader::read($obj->vcard, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
                if (! $vcalendar->UID) {
                    $vcalendar->UID = $obj->uuid;
                }
            } catch (ParseException $e) {
                // Ignore error
            }
        }

        if (! isset($vcalendar)) {
            // Basic information
            $vcalendar = new VCalendar([
                'UID' => $obj->uuid,
                'SOURCE' => $this->getSource($obj),
                'VERSION' => '2.0',
            ]);
        }

        $exporters = $this->exporters($obj::class);

        foreach ($exporters as $exporter) {
            $exporter->export($obj, $vcalendar);
        }

        return $vcalendar;
    }

    private function getSource(VCalendarResource $vcalendar): string
    {
        if ($vcalendar->contact instanceof Contact) {
            return route('contact.show', [
                'vault' => $vcalendar->contact->vault,
                'contact' => $vcalendar->contact,
            ]);
        } else {
            throw new ModelNotFoundException;
        }
    }

    /**
     * Get exporter instances.
     *
     * @param  class-string  $resourceClass
     * @return Collection<array-key,ExportVCalendarResource>
     */
    private function exporters(string $resourceClass): Collection
    {
        if (self::$exporters === null) {
            self::$exporters = collect(subClasses(ExportVCalendarResource::class))
                ->sortBy(fn (ReflectionClass $exporter) => Order::get($exporter))
                ->map(fn (ReflectionClass $exporter): ExportVCalendarResource => $exporter->newInstance());
        }

        return self::$exporters
            ->filter(fn (ExportVCalendarResource $exporter): bool => $exporter->getType() === $resourceClass);
    }
}
