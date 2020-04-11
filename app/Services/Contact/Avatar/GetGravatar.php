<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetGravatar extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::findOrFail($data['contact_id']);

        // prevent timestamp update
        $timestamps = $contact->timestamps;
        $contact->timestamps = false;

        $contact = $this->getGravatar($contact);
        $contact->save();

        $contact->timestamps = $timestamps;

        return $contact;
    }

    /**
     * Get the emails of the contact, based on the contact fields.
     *
     * @param Contact $contact
     * @return Collection
     */
    private function getEmails(Contact $contact)
    {
        $emails = collect();

        $contactFields = $contact->contactFields()
                                ->email()
                                ->get();
        foreach ($contactFields as $contactField) {
            try {
                $email = $contactField->data;

                Validator::make(['email' => $email], ['email' => 'email'])
                    ->validate();

                $emails->push($email);
            } catch (ModelNotFoundException $e) {
                // Not found
            } catch (ValidationException $e) {
                // Not an email
            }
        }

        return $emails;
    }

    /**
     * Query Gravatar (if it exists) for the contact's email address.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function getGravatar(Contact $contact)
    {
        $emails = $this->getEmails($contact);
        $gravatarUrl = null;

        foreach ($emails as $email) {
            $gravatarUrl = app(GetGravatarURL::class)->execute([
                'email' => $email,
                'size' => config('monica.avatar_size'),
            ]);
            if ($gravatarUrl) {
                break;
            }
        }

        if ($gravatarUrl) {
            $contact->avatar_gravatar_url = $gravatarUrl;
        } else {
            // in this case we need to make sure that we reset the gravatar URL
            $contact->avatar_gravatar_url = null;

            if ($contact->avatar_source == 'gravatar') {
                $contact->avatar_source = 'adorable';
            }
        }

        return $contact;
    }
}
