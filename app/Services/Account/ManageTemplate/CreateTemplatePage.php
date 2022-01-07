<?php

namespace App\Services\Account\ManageTemplate;

use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateTemplatePage extends BaseService implements ServiceInterface
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
     * Create a template page.
     *
     * @param  array  $data
     * @return TemplatePage
     */
    public function execute(array $data): TemplatePage
    {
        $this->validateRules($data);

        $this->template = Template::where('account_id', $data['account_id'])
            ->where('id', $data['template_id'])
            ->firstOrFail();

        // determine the new position of the template page
        $newPosition = TemplatePage::where('template_id', $data['template_id'])
            ->max('position');
        $newPosition++;

        $this->templatePage = TemplatePage::create([
            'template_id' => $data['template_id'],
            'name' => $data['name'],
            'position' => $newPosition,
        ]);

        return $this->templatePage;
    }
}
