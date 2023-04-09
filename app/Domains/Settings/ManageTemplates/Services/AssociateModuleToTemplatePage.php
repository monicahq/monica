<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Models\TemplatePage;
use App\Services\BaseService;

class AssociateModuleToTemplatePage extends BaseService implements ServiceInterface
{
    private array $data;

    private TemplatePage $templatePage;

    private Module $module;

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
     * Associate a module with a template page.
     */
    public function execute(array $data): Module
    {
        $this->data = $data;
        $this->validate();

        $newPosition = $this->templatePage->modules()->max('position') + 1;

        $this->templatePage->modules()->syncWithoutDetaching([
            $this->module->id => ['position' => $newPosition],
        ]);

        return $this->module;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->module = $this->account()->modules()
            ->findOrFail($this->data['module_id']);

        $template = $this->account()->templates()
            ->findOrFail($this->data['template_id']);

        $this->templatePage = $template->pages()
            ->findOrFail($this->data['template_page_id']);
    }
}
