<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PublishTemplateVersion;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateVersionResource;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TemplateVersionPublicationController extends Controller
{
    public function update(Request $request, string $template, string $version): JsonResponse
    {
        $template = $this->findTemplate($request, $template);
        $version = $this->findVersion($template, $version);

        $validated = $request->validate([
            'change_note' => ['nullable', 'string', 'max:65535'],
        ]);

        try {
            new PublishTemplateVersion(
                user: $request->user(),
                templateVersion: $version,
                changeNote: $validated['change_note'] ?? null,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['change_note' => $e->getMessage()]);
        } catch (PublishedVersionIsFrozen|CannotPublishAnEmptyPage $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return new TemplateVersionResource($version->refresh()->load(['template', 'previousVersion']))
            ->response()
            ->setStatusCode(200);
    }

    private function findTemplate(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function findVersion(Template $template, string $versionNumber): TemplateVersion
    {
        return $template->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();
    }
}
