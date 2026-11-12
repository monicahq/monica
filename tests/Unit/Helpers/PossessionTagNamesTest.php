<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\PossessionTagNames;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTagNamesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_trims_the_names_and_drops_the_empty_ones_and_the_repeated_ones(): void
    {
        $this->assertEquals(
            ['insured', 'office party'],
            PossessionTagNames::normalise(['  insured ', '', '<b>office party</b>', 'Insured', '   ']),
        );
    }

    #[Test]
    public function it_refuses_a_name_longer_than_fifty_characters(): void
    {
        $this->expectException(ValidationException::class);

        PossessionTagNames::normalise([str_repeat('a', 51)]);
    }

    #[Test]
    public function it_reuses_a_tag_of_the_account_ignoring_case_and_creates_the_others(): void
    {
        $user = User::factory()->create();
        $insured = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);
        PossessionTag::factory()->create(['name' => 'office party']);
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        PossessionTagNames::sync(user: $user, possession: $possession, names: ['insured', 'office party']);

        $this->assertTrue($possession->tags->contains($insured));
        $this->assertEquals(2, $user->account->possessionTags()->count());
        $this->assertEquals(['Insured', 'office party'], $possession->tags->pluck('name')->all());
    }
}
