<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ChangeContactTemplate;
use App\Enums\ApiKeyScopeEnum;
use App\Helpers\TemplateChangePreview;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateChangePreviewResource;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ContactTemplatePreviewController extends Controller
{
    public function show(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $validated = $request->validate([
            'template' => ['required', 'string', 'max:255'],
            'structure_version_id' => ['nullable', 'integer'],
        ]);

        $template = $request->user()->account->templates()->where('uuid', $validated['template'])->firstOrFail();

        $named = isset($validated['structure_version_id'])
            ? StructureVersion::query()->where('account_id', $request->user()->account_id)->whereKey($validated['structure_version_id'])->firstOrFail()
            : null;

        $refusal = ChangeContactTemplate::refusal($template, $named);

        if ($refusal !== null) {
            throw ValidationException::withMessages(['template' => $refusal]);
        }

        $target = $named ?? $template->structure->publishedVersion;

        $preview = new TemplateChangePreview(
            contact: $contact,
            target: $target,
            namesSensitiveFields: $this->reachesSensitiveFields($request->user()),
        )->toArray();

        return new TemplateChangePreviewResource([
            'contact' => $contact,
            'target' => $target,
            'preview' => $preview,
        ])->response()->setStatusCode(200);
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
