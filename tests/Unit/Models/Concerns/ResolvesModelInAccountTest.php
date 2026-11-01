<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    public function it_never_puts_a_value_that_does_not_read_as_a_uuid_to_the_uuid_column(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($user);

        $this->assertNull(new Vault()->resolveRouteBinding('dunder-mifflin'));
        $this->assertNotSame([], $queries);
        $this->assertSame([], array_filter($queries, fn (string $sql): bool => str_contains($sql, 'uuid')));
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
