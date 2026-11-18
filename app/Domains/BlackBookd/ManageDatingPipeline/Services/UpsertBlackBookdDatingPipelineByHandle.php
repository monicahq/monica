<?php

namespace App\Domains\BlackBookd\ManageDatingPipeline\Services;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdDatingPipeline;
use App\Models\Contact;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UpsertBlackBookdDatingPipelineByHandle extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'handle' => 'required|string|max:255',
            'contact_id' => 'nullable|uuid|exists:contacts,id',
            'app_source' => 'nullable|string|max:255',
            'age' => 'nullable|integer|between:18,120',
            'metro_location' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'looking_for' => 'nullable|string|max:255',
            'stage' => 'nullable|string|max:255',
            'last_message_from' => 'nullable|string|max:255',
            'last_message_summary' => 'nullable|string|max:65535',
            'last_message_at' => 'nullable|date',
            'what_he_said_matters' => 'nullable|string|max:65535',
            'next_step' => 'nullable|string|max:65535',
            'red_flags_notes' => 'nullable|string|max:65535',
            'phone_numbers_exchanged' => 'nullable|string|in:undecided,yes,no',
            'boundary_held_do_not_reengage' => 'nullable|boolean',
            'long_term_fit_score' => 'nullable|integer|between:1,10',
            'long_term_fit_reason' => 'nullable|string|max:65535',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): BlackBookdDatingPipeline
    {
        $this->validateRules($data);
        $handle = Str::lower(trim($data['handle']));

        $pipeline = $this->vault->blackbookdDatingPipelines()->where('handle', $handle)->first();
        $contact = isset($data['contact_id'])
            ? $this->vault->contacts()->findOrFail($data['contact_id'])
            : ($pipeline?->contact);

        if ($contact === null) {
            $contact = (new CreateContact)->execute([
                'account_id' => $data['account_id'],
                'vault_id' => $data['vault_id'],
                'author_id' => $data['author_id'],
                'first_name' => $data['handle'],
                'last_name' => null,
                'listed' => true,
                'template_id' => $this->vault->default_template_id,
            ]);
        }

        $attributes = [
            'vault_id' => $data['vault_id'],
            'contact_id' => $contact->id,
            'handle' => $handle,
            'app_source' => $this->valueOrNull($data, 'app_source'),
            'age' => $this->valueOrNull($data, 'age'),
            'metro_location' => $this->valueOrNull($data, 'metro_location'),
            'role' => $this->valueOrNull($data, 'role'),
            'looking_for' => $this->valueOrNull($data, 'looking_for'),
            'stage' => $data['stage'] ?? BlackBookdDatingPipeline::STAGE_FIRST_CONTACT,
            'last_message_from' => $this->valueOrNull($data, 'last_message_from'),
            'last_message_summary' => $this->valueOrNull($data, 'last_message_summary'),
            'last_message_at' => isset($data['last_message_at']) ? Carbon::parse($data['last_message_at']) : null,
            'what_he_said_matters' => $this->valueOrNull($data, 'what_he_said_matters'),
            'next_step' => $this->valueOrNull($data, 'next_step'),
            'red_flags_notes' => $this->valueOrNull($data, 'red_flags_notes'),
            'phone_numbers_exchanged' => $data['phone_numbers_exchanged'] ?? BlackBookdDatingPipeline::PHONE_UNDECIDED,
            'boundary_held_do_not_reengage' => $data['boundary_held_do_not_reengage'] ?? false,
            'long_term_fit_score' => $this->valueOrNull($data, 'long_term_fit_score'),
            'long_term_fit_reason' => $this->valueOrNull($data, 'long_term_fit_reason'),
        ];

        if ($pipeline === null) {
            $pipeline = BlackBookdDatingPipeline::create($attributes);
        } else {
            $pipeline->update($attributes);
        }

        $contact->last_updated_at = Carbon::now();
        $contact->save();

        return $pipeline->load('contact');
    }
}