<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Label;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $label = Label::factory()->create();

        $this->assertTrue($label->vault()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $ross = Contact::factory()->create([]);
        $label = Label::factory()->create();

        $label->contacts()->sync([$ross->id]);

        $this->assertTrue($label->contacts()->exists());
    }
}
