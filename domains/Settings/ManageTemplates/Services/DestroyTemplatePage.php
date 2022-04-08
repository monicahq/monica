<?php

namespace App\Settings\ManageTemplates\Services;

use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class DestroyTemplatePage extends BaseService implements ServiceInterface
{
    private Template $template;
    private TemplatePage $templatePage;

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
     * Destroy a template.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->template = Template::where('account_id', $data['account_id'])
            ->findOrFail($data['template_id']);

        $this->templatePage = TemplatePage::where('template_id', $data['template_id'])
            ->findOrFail($data['template_page_id']);

        $this->templatePage->delete();
    }
}
