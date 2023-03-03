<?php

namespace Tests\Unit\Models;

use App\Models\VaultQuickFactTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultQuickFactTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $quickFactTemplate = VaultQuickFactTemplate::factory()->create();

        $this->assertTrue($quickFactTemplate->vault()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $quickFactTemplate = VaultQuickFactTemplate::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $quickFactTemplate->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $quickFactTemplate = VaultQuickFactTemplate::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $quickFactTemplate->label
        );
    }
}
