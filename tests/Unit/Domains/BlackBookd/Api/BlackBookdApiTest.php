<?php

namespace Tests\Unit\Domains\BlackBookd\Api;

use App\Models\BlackBookdDatingPipeline;
use App\Models\Contact;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\ApiTestCase;

class BlackBookdApiTest extends ApiTestCase
{
    use DatabaseTransactions;

    private function enableBlackBookd($user): void
    {
        $user->account->update(['blackbookd_enabled' => true]);
    }

    /** @test */
    public function it_rejects_blackbookd_api_when_disabled(): void
    {
        $user = $this->createUser(['read', 'write']);
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $this->getJson('/api/vaults/'.$vault->id.'/blackbookd/encounters')->assertStatus(403);
    }

    /** @test */
    public function it_creates_encounter_and_pipeline_via_api(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1, 12));
        $user = $this->createUser(['read', 'write']);
        $this->enableBlackBookd($user);
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $response = $this->postJson('/api/vaults/'.$vault->id.'/blackbookd/encounters', [
            'occurred_at' => '2025-01-01T20:00:00Z',
            'location_label' => 'Portland metro',
            'how_met_category' => 'app',
            'how_met_app' => 'grindr',
            'protection_used' => ['condom', 'prep'],
            'activity_tags' => ['kissing'],
            'contact_ids' => [$contact->id],
        ]);
        $response->assertStatus(201);

        $pipelineResponse = $this->putJson('/api/vaults/'.$vault->id.'/blackbookd/dating-pipelines/by-handle', [
            'handle' => 'pdx_pup_example',
            'app_source' => 'fetlife',
            'stage' => BlackBookdDatingPipeline::STAGE_TALKING,
            'last_message_at' => '2025-01-01T18:00:00Z',
            'long_term_fit_score' => 8,
        ]);
        $pipelineResponse->assertSuccessful();
        $this->assertDatabaseHas('blackbookd_dating_pipelines', ['handle' => 'pdx_pup_example']);
    }

    /** @test */
    public function it_returns_weekly_roundup_and_quiet_pipelines(): void
    {
        $user = $this->createUser(['read', 'write']);
        $this->enableBlackBookd($user);
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        BlackBookdDatingPipeline::create([
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
            'handle' => 'quiet_one',
            'stage' => BlackBookdDatingPipeline::STAGE_TALKING,
            'last_message_at' => now()->subDays(14),
        ]);

        $this->getJson('/api/vaults/'.$vault->id.'/blackbookd/dating-pipelines/quiet?days=7')->assertSuccessful();
        $this->getJson('/api/vaults/'.$vault->id.'/blackbookd/dating-pipelines/weekly-roundup')->assertSuccessful();
    }
}