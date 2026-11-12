<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SupportRequestStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestStatusEnumTest extends TestCase
{
    #[Test]
    public function it_words_each_status_for_the_administrators_and_for_the_user(): void
    {
        $this->assertEquals('To handle', SupportRequestStatusEnum::ToHandle->administratorLabel());
        $this->assertEquals('Waiting for support', SupportRequestStatusEnum::ToHandle->userLabel());
        $this->assertEquals('Waiting on the user', SupportRequestStatusEnum::WaitingOnUser->administratorLabel());
        $this->assertEquals('Reply from support', SupportRequestStatusEnum::WaitingOnUser->userLabel());
        $this->assertEquals('Solved', SupportRequestStatusEnum::Solved->administratorLabel());
        $this->assertEquals('Solved', SupportRequestStatusEnum::Solved->userLabel());
    }

    #[Test]
    public function it_offers_both_wordings_for_translation(): void
    {
        $this->assertEquals(
            ['Waiting on the user', 'Reply from support'],
            SupportRequestStatusEnum::WaitingOnUser->translationKeys(),
        );
    }
}
