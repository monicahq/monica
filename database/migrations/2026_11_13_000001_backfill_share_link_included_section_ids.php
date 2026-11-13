<?php

declare(strict_types=1);

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contactType = new Contact()->getMorphClass();

        DB::table('share_links')
            ->orderBy('id')
            ->chunkById(200, function ($links) use ($contactType): void {
                foreach ($links as $link) {
                    DB::table('share_links')
                        ->where('id', $link->id)
                        ->update(['included_section_ids' => json_encode($link->shareable_type === $contactType ? $this->includedSectionIds($link) : [])]);
                }
            });
    }

    /** @return list<int> */
    private function includedSectionIds(object $link): array
    {
        $structureVersionId = DB::table('contacts')->where('id', $link->shareable_id)->value('structure_version_id');

        if ($structureVersionId === null) {
            return [];
        }

        $excludedPlacementIds = json_decode((string) $link->excluded_section_ids, true) ?: [];

        $excludedSectionIds = DB::table('structure_version_sections')
            ->join('section_versions', 'section_versions.id', '=', 'structure_version_sections.section_version_id')
            ->whereIn('structure_version_sections.id', $excludedPlacementIds)
            ->pluck('section_versions.section_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return DB::table('structure_version_sections')
            ->join('section_versions', 'section_versions.id', '=', 'structure_version_sections.section_version_id')
            ->where('structure_version_sections.structure_version_id', $structureVersionId)
            ->pluck('section_versions.section_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => in_array($id, $excludedSectionIds, true))
            ->values()
            ->all();
    }
};
