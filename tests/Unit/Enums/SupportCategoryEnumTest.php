<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Contracts\Translatable;
use App\Enums\SupportCategoryEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportCategoryEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_five_things_somebody_may_need_help_with(): void
    {
        $this->assertEquals(
            ['feature', 'billing', 'email', 'question', 'technical'],
            SupportCategoryEnum::values(),
        );
    }

    #[Test]
    public function it_offers_every_piece_of_its_guidance_for_translation(): void
    {
        $category = SupportCategoryEnum::Billing;

        $this->assertInstanceOf(Translatable::class, $category);
        $this->assertEquals(
            [
                'I have a billing question',
                'Billing',
                'Let’s sort out your billing.',
                'Tell us which payment, invoice or plan this is about, and the date if you have it.',
                'Never send your full card number. We never need it to help you.',
                'For example: October invoice shows the wrong company name',
            ],
            $category->translationKeys(),
        );
    }
}
