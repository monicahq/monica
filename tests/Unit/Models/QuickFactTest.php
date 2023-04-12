<?php

namespace Tests\Unit\Models;

use App\Models\QuickFact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuickFactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $quickFact = QuickFact::factory()->create();

        $this->assertTrue($quickFact->contact()->exists());
    }

    /** @test */
    public function it_has_one_vault_quick_fact_template_id()
    {
        $quickFact = QuickFact::factory()->create();

        $this->assertTrue($quickFact->vaultQuickFactsTemplate()->exists());
    }
}
