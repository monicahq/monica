<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Label;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
