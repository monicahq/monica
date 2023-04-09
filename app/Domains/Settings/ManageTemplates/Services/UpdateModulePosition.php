<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Models\TemplatePage;
use App\Services\BaseService;

class UpdateModulePosition extends BaseService implements ServiceInterface
{
    private TemplatePage $templatePage;

    private Module $module;

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
            'module_id' => 'required|integer|exists:modules,id',
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
     * Update the module position.
     */
    public function execute(array $data): Module
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->module;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $template = $this->account()->templates()
            ->findOrFail($this->data['template_id']);

        $this->templatePage = $template->pages()
            ->findOrFail($this->data['template_page_id']);

        $this->module = $this->templatePage->modules()
            ->withPivot('position')
            ->findOrFail($this->data['module_id']);

        $this->pastPosition = $this->module->pivot->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->module->pivot
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->templatePage->modules()
            ->wherePivot('position', '>', $this->pastPosition)
            ->wherePivot('position', '<=', $this->data['new_position'])
            ->newPivotQuery()
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->templatePage->modules()
            ->wherePivot('position', '>=', $this->data['new_position'])
            ->wherePivot('position', '<', $this->pastPosition)
            ->newPivotQuery()
            ->increment('position');
    }
}
