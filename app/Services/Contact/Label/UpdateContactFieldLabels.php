<?php

namespace App\Services\Contact\Label;

use App\Services\BaseService;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldLabel;

class UpdateContactFieldLabels extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_field_id' => 'required|integer|exists:contact_fields,id',
            'labels' => 'required|array',
        ];
    }

    /**
     * Update contact field's labels.
     *
     * @param array $data
     * @return void
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $contactField = ContactField::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_field_id']);

        $labelsId = $this->getLabelsId($data);

        $this->updateLabels($labelsId, $contactField);
    }

    /**
     * Get ContactFieldLabel ids.
     *
     * @param array $data
     * @return array
     */
    private function getLabelsId(array $data): array
    {
        $labelsId = [];
        foreach ($data['labels'] as $label) {
            $label2 = mb_strtolower($label);
            $s = ContactFieldLabel::$standardLabels;
            if (in_array($label2, ContactFieldLabel::$standardLabels)) {
                $labelsId[] = (ContactFieldLabel::firstOrCreate([
                    'account_id' => $data['account_id'],
                    'label_i18n' => $label2,
                ]))->id;
            } else {
                $labelsId[] = (ContactFieldLabel::firstOrCreate([
                    'account_id' => $data['account_id'],
                    'label' => $label,
                ]))->id;
            }
        }

        return $labelsId;
    }

    /**
     * Update contactField's labels.
     *
     * @param array $labelsId
     * @param ContactField $contactField
     * @return void
     */
    private function updateLabels(array $labelsId, ContactField $contactField)
    {
        $labelsSync = [];
        foreach ($labelsId as $labelId) {
            $labelsSync[$labelId] = ['account_id' => $contactField->account_id];
        }

        $contactField->labels()->sync($labelsSync);
    }
}
