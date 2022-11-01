<?php

namespace App\Domains\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplate;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdatePostTemplatePosition extends BaseService implements ServiceInterface
{
    private PostTemplate $postTemplate;

    private array $data;

    private int $pastPosition;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'post_template_id' => 'required|integer|exists:post_templates,id',
            'new_position' => 'required|integer',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update the post type position.
     *
     * @param  array  $data
     * @return PostTemplate
     */
    public function execute(array $data): PostTemplate
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->postTemplate;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->postTemplate = PostTemplate::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['post_template_id']);

        $this->pastPosition = $this->postTemplate->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('post_templates')
            ->where('account_id', $this->data['account_id'])
            ->where('id', $this->postTemplate->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('post_templates')
            ->where('account_id', $this->data['account_id'])
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('post_templates')
            ->where('account_id', $this->data['account_id'])
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
