<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User\User;
use Illuminate\Support\Arr;
use App\Models\Contact\Contact;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Support\Facades\Validator;

abstract class BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Validate all datas to execute the service.
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        Validator::make($data, $this->rules())
            ->validate();

        return true;
    }

    /**
     * Checks if the value is empty or null.
     *
     * @param mixed $data
     * @param mixed $index
     * @return mixed
     */
    public function nullOrValue($data, $index)
    {
        $value = Arr::get($data, $index, null);

        return is_null($value) || $value === '' ? null : $value;
    }

    /**
     * Checks if the value is empty or null and returns a date from a string.
     *
     * @param mixed $data
     * @param mixed $index
     * @return mixed
     */
    public function nullOrDate($data, $index)
    {
        $value = Arr::get($data, $index, null);

        return is_null($value) || $value === '' ? null : Carbon::parse($value);
    }

    /**
     * Returns the value if it's defined, or false otherwise.
     *
     * @param mixed $data
     * @param mixed $index
     * @return mixed
     */
    public function valueOrFalse($data, $index)
    {
        if (empty($data[$index])) {
            return false;
        }

        return $data[$index];
    }

    /**
     * Add an audit log for the given action.
     * This triggers a job, that triggers the actual service to write the log.
     *
     * @param User $author
     * @param string $action
     * @param array $objects
     * @param bool $displayOnDashboard
     * @return void
     */
    public function writeAuditLog(User $author, string $action, array $objects, bool $displayOnDashboard = false): void
    {
        LogAccountAudit::dispatch([
            'action' => $action,
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => Carbon::now(),
            'should_appear_on_dashboard' => $displayOnDashboard,
            'objects' => json_encode($objects),
        ]);
    }

    /**
     * Add an audit log for the given action for a specific contact.
     * Note that this creates an audit log in the Settings page, and we'll use
     * this info to display all the logs made about the given contact.
     *
     * @param User $author
     * @param string $action
     * @param array $objects
     * @param Contact $contact
     * @return void
     */
    public function writeContactAuditLog(User $author, string $action, array $objects, Contact $contact, bool $displayOnDashboard = false): void
    {
        LogAccountAudit::dispatch([
            'action' => $action,
            'account_id' => $author->account_id,
            'about_contact_id' => $contact->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => Carbon::now(),
            'should_appear_on_dashboard' => $displayOnDashboard,
            'objects' => json_encode($objects),
        ]);
    }
}
