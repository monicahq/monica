<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateModulePosition extends BaseService implements ServiceInterface
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
            'new_position' => 'required|integer',
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
     * Update the module position.
     *
     * @param  array  $data
     * @return Module
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

        $this->template = Template::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['template_id']);

        $this->templatePage = TemplatePage::where('template_id', $this->data['template_id'])
            ->findOrFail($this->data['template_page_id']);

        $this->module = Module::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['module_id']);

        $this->pastPosition = DB::table('module_template_page')
            ->where('template_page_id', $this->templatePage->id)
            ->where('module_id', $this->module->id)
            ->select('position')
            ->first()->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('module_template_page')
            ->where('template_page_id', $this->templatePage->id)
            ->where('module_id', $this->module->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('module_template_page')
            ->where('template_page_id', $this->templatePage->id)
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('module_template_page')
            ->where('template_page_id', $this->templatePage->id)
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
