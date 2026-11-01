<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupVCardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_downloads_the_group_as_a_card_file(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();

        $response = $this->actingAs($user)->get(route('groups.vcard.show', ['vault' => $vault, 'group' => $group]));

        $response->assertOk();
        $response->assertDownload('finer-things-club.vcf');
        $this->assertStringContainsString('KIND:group', $response->streamedContent());
    }

    #[Test]
    public function it_is_offered_from_the_group_page_only_while_the_export_is_on(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $url = route('groups.vcard.show', ['vault' => $vault, 'group' => $group]);

        $this->actingAs($user)->get(route('groups.show', ['vault' => $vault, 'group' => $group]))->assertSee($url, false);

        $user->account->features()->deactivate('vcard-export');
        Feature::flushCache();

        $this->actingAs($user)->get(route('groups.show', ['vault' => $vault, 'group' => $group]))->assertDontSee($url, false);
        $this->actingAs($user)->get($url)->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function finerThingsClub(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club'])];
    }
}
