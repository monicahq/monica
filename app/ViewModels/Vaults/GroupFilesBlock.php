<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class GroupFilesBlock
{
    public const int PANEL_COUNT = 3;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
    ) {}

    /**
     * The files kept with the group, the most recently added first.
     *
     * @return list<array{name: string, meta: string, icon: string, url: string, destroyUrl: string}>
     */
    public function files(?int $limit = null): array
    {
        $period = new GroupPeriod($this->user);

        return GroupFile::query()
            ->where('group_id', $this->group->id)
            ->whereHas('file')
            ->with('file')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->when($limit !== null, fn ($query) => $query->limit((int) $limit))
            ->get()
            ->map(fn (GroupFile $attachment): array => [
                'name' => $attachment->file->original_name,
                'meta' => implode(' · ', [
                    $this->kindOf($attachment),
                    Number::fileSize($attachment->file->size_in_bytes, precision: 1),
                    __('added :date', ['date' => $period->date($attachment->created_at, DatePrecisionEnum::Day)]),
                ]),
                'icon' => str_starts_with($attachment->file->mime_type, 'image/') ? 'memory' : 'file',
                'url' => route('files.show', ['vault' => $this->vault, 'file' => $attachment->file]),
                'destroyUrl' => route('groups.files.destroy', ['vault' => $this->vault, 'group' => $this->group, 'groupFile' => $attachment]),
            ])
            ->values()
            ->all();
    }

    /**
     * The few files the side panel of the overview shows.
     *
     * @return list<array{name: string, meta: string, icon: string, url: string, destroyUrl: string}>
     */
    public function latest(): array
    {
        return $this->files(self::PANEL_COUNT);
    }

    public function count(): int
    {
        return GroupFile::query()->where('group_id', $this->group->id)->whereHas('file')->count();
    }

    public function createUrl(): string
    {
        return route('groups.files.create', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function indexUrl(): string
    {
        return route('groups.files.index', ['vault' => $this->vault, 'group' => $this->group]);
    }

    private function kindOf(GroupFile $attachment): string
    {
        if (str_starts_with($attachment->file->mime_type, 'image/')) {
            return __('Photo');
        }

        $extension = pathinfo($attachment->file->original_name, PATHINFO_EXTENSION);

        return $extension === '' ? __('File') : Str::upper($extension);
    }
}
