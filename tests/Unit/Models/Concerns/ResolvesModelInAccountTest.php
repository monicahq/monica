<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolvesModelInAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_model_naming_one_column_resolves_by_it(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user);

        $this->assertTrue($template->is(new Template()->resolveRouteBinding($template->slug)));
    }

    #[Test]
    public function a_model_naming_one_column_still_refuses_every_other_spelling(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user);

        $this->assertNull(new Template()->resolveRouteBinding($template->uuid));
    }

    #[Test]
    public function it_still_accepts_the_primary_key_as_a_second_spelling(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user);

        $this->assertTrue($template->is(new Template()->resolveRouteBinding((string) $template->id)));
    }

    #[Test]
    public function it_still_honours_a_column_the_route_names_outright(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user);

        $this->assertTrue($template->is(new Template()->resolveRouteBinding($template->uuid, 'uuid')));
        $this->assertNull(new Template()->resolveRouteBinding($template->slug, 'uuid'));
    }

    #[Test]
    public function it_still_refuses_a_record_of_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($user);

        $this->assertNotSame($user->account_id, $template->account_id);
        $this->assertNull(new Template()->resolveRouteBinding($template->slug));
    }
}
