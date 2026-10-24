<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Enums\McpCallResultEnum;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\McpCall;
use App\ViewModels\Settings\Account\Security\McpCallsViewModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class McpCallController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'period' => ['nullable', Rule::in(array_keys(McpCall::PERIODS))],
            'connection' => ['nullable', 'string', 'regex:/^(api_key|oauth_client):.+$/'],
            'result' => ['nullable', Rule::enum(McpCallResultEnum::class)],
            'tool' => ['nullable', 'string', 'max:255'],
        ]);

        return view('app.settings.account.security.mcp-calls', [
            'viewModel' => new McpCallsViewModel(
                user: $request->user(),
                size: PageSize::from($request, McpCallsViewModel::PER_PAGE),
                period: $request->filled('period') && $request->string('period')->toString() !== 'all' ? $request->string('period')->toString() : null,
                connection: $request->filled('connection') ? $request->string('connection')->toString() : null,
                result: $request->filled('result') ? $request->string('result')->toString() : null,
                tool: $request->filled('tool') ? $request->string('tool')->toString() : null,
            ),
        ]);
    }
}
