<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyField;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_field_out_of_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $version->id, 'label' => 'Street']);

        new DestroyField(user: $user, field: $field)->execute();

        $this->assertModelMissing($field);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::FieldDeletion
                && $job->parameters === ['label' => 'Street', 'name' => 'Address'];
        });
    }

    #[Test]
    public function it_takes_what_sits_inside_a_repeater_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id]);
        $child = Field::factory()->create([
            'section_version_id' => $version->id,
            'parent_field_id' => $repeater->id,
        ]);

        new DestroyField(user: $user, field: $repeater)->execute();

        $this->assertModelMissing($child);
    }

    #[Test]
    public function it_refuses_to_take_a_field_out_of_a_published_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $version->id]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroyField(user: $user, field: $field->fresh())->execute();
    }

    #[Test]
    public function it_refuses_a_field_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $field = Field::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyField(user: $michael, field: $field)->execute();
    }
}
