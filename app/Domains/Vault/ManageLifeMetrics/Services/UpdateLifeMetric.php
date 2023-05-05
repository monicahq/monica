<?php

namespace App\Domains\Vault\ManageLifeMetrics\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeMetric;
use App\Services\BaseService;

class UpdateLifeMetric extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'life_metric_id' => 'required|integer|exists:life_metrics,id',
            'label' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Update a life metric.
     */
    public function execute(array $data): LifeMetric
    {
        $this->validateRules($data);

        $lifeMetric = $this->vault->lifeMetrics()
            ->findOrFail($data['life_metric_id']);

        $lifeMetric->label = $data['label'];
        $lifeMetric->save();

        return $lifeMetric;
    }
}
