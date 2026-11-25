<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{
 *     contact: Contact,
 *     target: StructureVersion,
 *     preview: array{
 *         isChange: bool,
 *         setAside: list<array{field_id: int, label: string, section: string, count: int}>,
 *         broughtBack: list<array{field_id: int, label: string, section: string, count: int}>,
 *         remindersStopped: list<array{id: string, label: string}>,
 *         remindersRestarted: list<array{id: string, label: string}>,
 *         hiddenCount: int
 *     }
 * } $resource
 */
class TemplateChangePreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $preview = $this->resource['preview'];

        return [
            'type' => 'template_change_preview',
            'attributes' => [
                'is_change' => $preview['isChange'],
                'from' => $this->version($this->resource['contact']->structureVersion),
                'to' => $this->version($this->resource['target']),
                'set_aside' => $preview['setAside'],
                'brought_back' => $preview['broughtBack'],
                'reminders_stopped' => $preview['remindersStopped'],
                'reminders_restarted' => $preview['remindersRestarted'],
                'hidden_count' => $preview['hiddenCount'],
            ],
        ];
    }

    /** @return array{template: string|null, template_name: string|null, structure_version_id: int, version_number: int} */
    private function version(StructureVersion $version): array
    {
        $owner = $version->structure->owner;

        return [
            'template' => $owner instanceof Template ? $owner->uuid : null,
            'template_name' => $owner instanceof Template ? $owner->name : null,
            'structure_version_id' => $version->id,
            'version_number' => $version->version_number,
        ];
    }
}
