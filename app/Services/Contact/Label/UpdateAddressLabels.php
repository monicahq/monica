<?php

namespace App\Services\Contact\Label;

use App\Services\BaseService;
use App\Models\Contact\Address;
use App\Models\Contact\ContactFieldLabel;

class UpdateAddressLabels extends BaseService
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
            'address_id' => 'required|integer|exists:addresses,id',
            'labels' => 'required|array',
        ];
    }

    /**
     * Update address' labels.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $address = Address::where('account_id', $data['account_id'])
            ->findOrFail($data['address_id']);

        $address->contact->throwInactive();

        $labelsId = $this->getLabelsId($data);

        $this->updateLabels($labelsId, $address);
    }

    /**
     * Get ContactFieldLabel ids.
     *
     * @param  array  $data
     * @return array
     */
    private function getLabelsId(array $data): array
    {
        $labelsId = [];
        foreach ($data['labels'] as $label) {
            $label2 = mb_strtolower($label);
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
     * @param  array  $labelsId
     * @param  Address  $address
     * @return void
     */
    private function updateLabels(array $labelsId, Address $address)
    {
        $labelsSync = [];
        foreach ($labelsId as $labelId) {
            $labelsSync[$labelId] = ['account_id' => $address->account_id];
        }

        $address->labels()->sync($labelsSync);
    }
}
