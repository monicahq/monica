<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;

class UpdateTemplatePagePosition extends BaseService implements ServiceInterface
{
    private Template $template;

    private TemplatePage $templatePage;

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
            'template_id' => 'required|integer|exists:templates,id',
            'template_page_id' => 'required|integer|exists:template_pages,id',
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
     * Update the template page position.
     */
    public function execute(array $data): TemplatePage
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->templatePage;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->template = $this->account()->templates()
            ->findOrFail($this->data['template_id']);

        $this->templatePage = $this->template->pages()
            ->findOrFail($this->data['template_page_id']);

        $this->pastPosition = $this->templatePage->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->templatePage
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->template->pages()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->template->pages()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
