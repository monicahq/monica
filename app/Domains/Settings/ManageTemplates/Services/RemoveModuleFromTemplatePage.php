<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Models\TemplatePage;
use App\Services\BaseService;

class RemoveModuleFromTemplatePage extends BaseService implements ServiceInterface
{
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
     * Remove a module from a template page.
     */
    public function execute(array $data): Module
    {
        $this->validateRules($data);

        $this->module = $this->account()->modules()
            ->findOrFail($data['module_id']);

        $template = $this->account()->templates()
            ->findOrFail($data['template_id']);

        $this->templatePage = $template->pages()
            ->findOrFail($data['template_page_id']);

        $this->templatePage->modules()->toggle([
            $this->module->id,
        ]);

        return $this->module;
    }
}
