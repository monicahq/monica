<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplatePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplatePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_template_up_the_picker(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Template::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $pet = Template::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $car = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Car', 'position' => 3]);

        new UpdateTemplatePosition(user: $user, template: $car, position: 1)->execute();

        $this->assertEquals(1, $car->fresh()->position);
        $this->assertEquals(2, $contact->fresh()->position);
        $this->assertEquals(3, $pet->fresh()->position);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplatePositionUpdate
                && $job->parameters === ['name' => 'Car'];
        });
    }

    #[Test]
    public function it_puts_a_template_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Template::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $pet = Template::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateTemplatePosition(user: $user, template: $contact, position: 99)->execute();

        $this->assertEquals(1, $pet->fresh()->position);
        $this->assertEquals(2, $contact->fresh()->position);
    }

    #[Test]
    public function it_leaves_the_templates_of_another_account_where_they_are(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $mine = Template::factory()->create(['account_id' => $michael->account_id, 'position' => 1]);
        $theirs = Template::factory()->create(['account_id' => $dwight->account_id, 'position' => 1]);

        new UpdateTemplatePosition(user: $michael, template: $mine, position: 2)->execute();

        $this->assertEquals(1, $theirs->fresh()->position);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplatePosition(user: $michael, template: $template, position: 1)->execute();
    }
}
