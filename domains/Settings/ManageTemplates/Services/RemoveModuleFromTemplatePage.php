<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;

class RemoveModuleFromTemplatePage extends BaseService implements ServiceInterface
{
    private TemplatePage $templatePage;
    private Module $module;

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
            'template_id' => 'required|integer|exists:templates,id',
            'template_page_id' => 'required|integer|exists:template_pages,id',
            'module_id' => 'required|integer|exists:modules,id',
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
     * Remove a module from a template page.
     *
     * @param  array  $data
     * @return Module
     */
    public function execute(array $data): Module
    {
        $this->validateRules($data);

        $this->module = Module::where('account_id', $data['account_id'])
            ->findOrFail($data['module_id']);

        $this->template = Template::where('account_id', $data['account_id'])
            ->findOrFail($data['template_id']);

        $this->templatePage = TemplatePage::where('template_id', $data['template_id'])
            ->findOrFail($data['template_page_id']);

        $this->templatePage->modules()->toggle([
            $this->module->id,
        ]);

        return $this->module;
    }
}
