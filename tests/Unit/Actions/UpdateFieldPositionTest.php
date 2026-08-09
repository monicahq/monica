<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateFieldPosition;
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

class UpdateFieldPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_up_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $street = Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);
        $city = Field::factory()->create(['section_version_id' => $version->id, 'position' => 2]);
        $country = Field::factory()->create(['section_version_id' => $version->id, 'position' => 3]);

        new UpdateFieldPosition(user: $user, field: $country, position: 1)->execute();

        $this->assertEquals(1, $country->fresh()->position);
        $this->assertEquals(2, $street->fresh()->position);
        $this->assertEquals(3, $city->fresh()->position);
    }

    #[Test]
    public function it_puts_a_field_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $street = Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);
        $city = Field::factory()->create(['section_version_id' => $version->id, 'position' => 2]);

        new UpdateFieldPosition(user: $user, field: $street, position: 99)->execute();

        $this->assertEquals(1, $city->fresh()->position);
        $this->assertEquals(2, $street->fresh()->position);
    }

    /**
     * A field inside a repeater is ordered against the other fields in that
     * repeater, not against the whole section.
     */
    #[Test]
    public function it_moves_a_field_among_its_own_siblings_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $outside = Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id, 'position' => 2]);
        $label = Field::factory()->create([
            'section_version_id' => $version->id,
            'parent_field_id' => $repeater->id,
            'position' => 1,
        ]);
        $number = Field::factory()->create([
            'section_version_id' => $version->id,
            'parent_field_id' => $repeater->id,
            'position' => 2,
        ]);

        new UpdateFieldPosition(user: $user, field: $number, position: 1)->execute();

        $this->assertEquals(1, $number->fresh()->position);
        $this->assertEquals(2, $label->fresh()->position);
        $this->assertEquals(1, $outside->fresh()->position);
    }

    #[Test]
    public function it_refuses_to_reorder_a_published_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateFieldPosition(user: $user, field: $field->fresh(), position: 1)->execute();
    }

    #[Test]
    public function it_refuses_a_field_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $field = Field::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateFieldPosition(user: $michael, field: $field, position: 1)->execute();
    }

    #[Test]
    public function it_writes_the_move_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $version->id, 'label' => 'Street']);

        new UpdateFieldPosition(user: $user, field: $field, position: 1)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::FieldPositionUpdate
                && $job->parameters === ['label' => 'Street', 'name' => 'Address'];
        });
    }
}
