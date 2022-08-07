<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use Illuminate\Support\Str;

class UpdateTemplatePage extends BaseService implements ServiceInterface
{
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
            'name' => 'required|string|max:255',
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
     * Update a template page.
     *
     * @param  array  $data
     * @return TemplatePage
     */
    public function execute(array $data): TemplatePage
    {
        $this->validateRules($data);

        Template::where('account_id', $data['account_id'])
            ->findOrFail($data['template_id']);

        $this->templatePage = TemplatePage::where('template_id', $data['template_id'])
            ->findOrFail($data['template_page_id']);

        $this->templatePage->name = $data['name'];
        $this->templatePage->slug = Str::slug($data['name'], '-');
        $this->templatePage->save();

        return $this->templatePage;
    }
}
