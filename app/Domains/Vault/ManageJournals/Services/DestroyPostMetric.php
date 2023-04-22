<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostMetric;
use App\Services\BaseService;

class DestroyPostMetric extends BaseService implements ServiceInterface
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
            'post_metric_id' => 'required|integer|exists:post_metrics,id',
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

    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validate();
        $this->postMetric->delete();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $post = $journal->posts()->findOrfail($this->data['post_id']);

        $this->postMetric = $post->postMetrics()
            ->findOrFail($this->data['post_metric_id']);
    }
}
