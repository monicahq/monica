<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostMetric;
use App\Services\BaseService;

class UpdatePostMetric extends BaseService implements ServiceInterface
{
    private array $data;

    private PostMetric $postMetric;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'journal_id' => 'required|integer|exists:journals,id',
            'post_id' => 'required|integer|exists:posts,id',
            'journal_metric_id' => 'required|integer|exists:journal_metrics,id',
            'value' => 'required|integer',
            'label' => 'nullable|string|max:255',
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
            'author_must_be_vault_editor',
        ];
    }

    public function execute(array $data): PostMetric
    {
        $this->data = $data;

        $this->validate();
        $this->update();

        return $this->postMetric;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrfail($this->data['journal_id']);

        $journal->posts()->findOrfail($this->data['post_id']);

        $journalMetric = $journal->journalMetrics()
            ->findOrfail($this->data['journal_metric_id']);

        $this->postMetric = $journalMetric->postMetrics()
            ->findOrfail($this->data['post_metric_id']);
    }

    private function update(): void
    {
        $this->postMetric->label = $this->valueOrNull($this->data, 'label');
        $this->postMetric->value = $this->data['value'];
        $this->postMetric->save();
    }
}
