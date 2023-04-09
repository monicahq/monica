<?php

namespace App\Domains\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Services\BaseService;

class UpdatePostTemplateSectionPosition extends BaseService implements ServiceInterface
{
    private PostTemplate $postTemplate;

    private PostTemplateSection $postTemplateSection;

    private array $data;

    private int $pastPosition;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'post_template_id' => 'required|integer|exists:post_templates,id',
            'post_template_section_id' => 'required|integer|exists:post_template_sections,id',
            'new_position' => 'required|integer',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update the post type section position.
     */
    public function execute(array $data): PostTemplateSection
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->postTemplateSection;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->postTemplate = $this->account()->postTemplates()
            ->findOrFail($this->data['post_template_id']);

        $this->postTemplateSection = $this->postTemplate->postTemplateSections()
            ->findOrFail($this->data['post_template_section_id']);

        $this->pastPosition = $this->postTemplateSection->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->postTemplateSection
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->postTemplate->postTemplateSections()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->postTemplate->postTemplateSections()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
