<?php

namespace App\Services\Contact\Contact;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Helpers\AccountHelper;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Jobs\Avatars\GenerateDefaultAvatar;
use App\Services\Contact\Description\SetPersonalDescription;
use App\Services\Contact\Description\ClearPersonalDescription;

class UpdateContact extends BaseService
{
    private array $data;
    private Contact $contact;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer',
            'uuid' => 'nullable|string',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'description' => 'nullable|string|max:255',
            'is_partial' => 'nullable|boolean',
            'is_birthdate_known' => 'required|boolean',
            'birthdate_day' => 'nullable|integer',
            'birthdate_month' => 'nullable|integer',
            'birthdate_year' => 'nullable|integer',
            'birthdate_is_age_based' => 'nullable|boolean',
            'birthdate_age' => 'nullable|integer',
            'birthdate_add_reminder' => 'nullable|boolean',
            'is_deceased' => 'nullable|boolean',
            'is_deceased_date_known' => 'required|boolean',
            'deceased_date_day' => 'nullable|integer',
            'deceased_date_month' => 'nullable|integer',
            'deceased_date_year' => 'nullable|integer',
            'deceased_date_add_reminder' => 'nullable|boolean',
        ];
    }

    /**
     * Update a contact.
     *
     * @param  array  $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate($this->data);

        /* @var Contact */
        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->contact->throwInactive();

        // Test is the account is limited and the contact should be updated as real contact
        $account = Account::find($data['account_id']);
        if ($this->contact->is_partial
            && ! $this->valueOrFalse($this->data, 'is_partial')
            && AccountHelper::hasReachedContactLimit($account)
            && AccountHelper::hasLimitations($account)
            && ! $account->legacy_free_plan_unlimited_contacts) {
            abort(402);
        }

        $this->updateGeneralInformation();
        $this->updateDescription();
        $this->updateBirthDayInformation();
        $this->updateDeceasedInformation();

        return $this->contact->refresh();
    }

    private function updateGeneralInformation(): void
    {
        // filter out the data that shall not be updated here
        $dataOnly = Arr::except(
            $this->data,
            [
                'author_id',
                'uuid',
                'is_birthdate_known',
                'birthdate_day',
                'birthdate_month',
                'birthdate_year',
                'birthdate_is_age_based',
                'birthdate_age',
                'birthdate_add_reminder',
                'is_deceased',
                'is_deceased_date_known',
                'deceased_date_day',
                'deceased_date_month',
                'deceased_date_year',
                'deceased_date_add_reminder',
                'description',
            ]
        );

        if (! empty($uuid = Arr::get($this->data, 'uuid')) && Uuid::isValid($uuid)) {
            $dataOnly['uuid'] = $uuid;
        }

        $oldName = $this->contact->name;
        $this->contact->update($dataOnly);

        // only update the avatar if the name has changed
        if ($oldName != $this->contact->name) {
            GenerateDefaultAvatar::dispatch($this->contact);
        }
    }

    private function updateDescription(): void
    {
        if (is_null($this->nullOrValue($this->data, 'description'))) {
            app(ClearPersonalDescription::class)->execute([
                'account_id' => $this->data['account_id'],
                'contact_id' => $this->data['contact_id'],
                'author_id' => $this->data['author_id'],
            ]);
        } else {
            if ($this->contact->description != $this->data['description']) {
                app(SetPersonalDescription::class)->execute([
                    'account_id' => $this->data['account_id'],
                    'contact_id' => $this->data['contact_id'],
                    'author_id' => $this->data['author_id'],
                    'description' => $this->data['description'],
                ]);
            }
        }
    }

    private function updateBirthDayInformation(): void
    {
        app(UpdateBirthdayInformation::class)->execute([
            'account_id' => $this->data['account_id'],
            'contact_id' => $this->contact->id,
            'is_date_known' => $this->data['is_birthdate_known'],
            'day' => $this->nullOrvalue($this->data, 'birthdate_day'),
            'month' => $this->nullOrvalue($this->data, 'birthdate_month'),
            'year' => $this->nullOrvalue($this->data, 'birthdate_year'),
            'is_age_based' => $this->nullOrvalue($this->data, 'birthdate_is_age_based'),
            'age' => $this->nullOrvalue($this->data, 'birthdate_age'),
            'add_reminder' => $this->nullOrvalue($this->data, 'birthdate_add_reminder'),
            'is_deceased' => $this->data['is_deceased'],
        ]);
    }

    private function updateDeceasedInformation(): void
    {
        app(UpdateDeceasedInformation::class)->execute([
            'account_id' => $this->data['account_id'],
            'contact_id' => $this->contact->id,
            'is_deceased' => $this->data['is_deceased'],
            'is_date_known' => $this->data['is_deceased_date_known'],
            'day' => $this->nullOrvalue($this->data, 'deceased_date_day'),
            'month' => $this->nullOrvalue($this->data, 'deceased_date_month'),
            'year' => $this->nullOrvalue($this->data, 'deceased_date_year'),
            'add_reminder' => $this->nullOrvalue($this->data, 'deceased_date_add_reminder'),
        ]);
    }
}
