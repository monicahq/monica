<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\RemoveOrphanedInstanceSetting;
use App\Actions\RevertInstanceSetting;
use App\Actions\UpdateInstanceSetting;
use App\Enums\InstanceSettingEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Admin\ConfigurationViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstanceSettingController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.configuration.index', [
            'viewModel' => new ConfigurationViewModel(user: $request->user()),
        ]);
    }

    public function update(Request $request, InstanceSettingEnum $setting): RedirectResponse
    {
        new UpdateInstanceSetting(
            administrator: $request->user(),
            setting: $setting,
            value: $request->string('value')->toString(),
        )->execute();

        return redirect()->route('admin.configuration.index')
            ->with('status', __('The setting is saved.'))
            ->with('status_description', __('It governs from the next request. Work already running finishes under the value it started with.'));
    }

    public function destroy(Request $request, InstanceSettingEnum $setting): RedirectResponse
    {
        new RevertInstanceSetting(
            administrator: $request->user(),
            setting: $setting,
        )->execute();

        return redirect()->route('admin.configuration.index')
            ->with('status', __('The setting is back to what the deployment supplies.'));
    }

    public function destroyOrphan(Request $request, string $name): RedirectResponse
    {
        new RemoveOrphanedInstanceSetting(
            administrator: $request->user(),
            name: $name,
        )->execute();

        return redirect()->route('admin.configuration.index')
            ->with('status', __('The value nothing reads any more is removed.'));
    }
}
