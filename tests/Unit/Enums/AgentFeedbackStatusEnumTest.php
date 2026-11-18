<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AgentFeedbackStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackStatusEnumTest extends TestCase
{
    #[Test]
    public function it_lets_a_new_report_be_accepted_or_dismissed(): void
    {
        $this->assertSame([AgentFeedbackStatusEnum::Accepted, AgentFeedbackStatusEnum::Dismissed], AgentFeedbackStatusEnum::New->allowedTargets());
    }

    #[Test]
    public function it_lets_an_accepted_report_be_fixed_or_dismissed(): void
    {
        $this->assertSame([AgentFeedbackStatusEnum::Fixed, AgentFeedbackStatusEnum::Dismissed], AgentFeedbackStatusEnum::Accepted->allowedTargets());
    }

    #[Test]
    public function it_lets_a_closed_report_only_be_reopened(): void
    {
        $this->assertSame([AgentFeedbackStatusEnum::New], AgentFeedbackStatusEnum::Fixed->allowedTargets());
        $this->assertSame([AgentFeedbackStatusEnum::New], AgentFeedbackStatusEnum::Dismissed->allowedTargets());
    }
}
