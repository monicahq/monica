<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use Illuminate\Support\Str;

class CreateTemplatePage extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'can_be_deleted' => 'nullable|boolean',
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

        Template::where('account_id', $data['account_id'])
            ->where('id', $data['template_id'])
            ->firstOrFail();

        // determine the new position of the template page
        $newPosition = TemplatePage::where('template_id', $data['template_id'])
            ->max('position');
        $newPosition++;

        $this->templatePage = TemplatePage::create([
            'template_id' => $data['template_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name'], '-'),
            'type' => $this->valueOrNull($data, 'type'),
            'position' => $newPosition,
            'can_be_deleted' => $this->valueOrTrue($data, 'can_be_deleted'),
        ]);

        return $this->templatePage;
    }
}
