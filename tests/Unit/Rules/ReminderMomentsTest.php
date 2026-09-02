<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\ReminderMoments;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderMomentsTest extends TestCase
{
    #[Test]
    public function it_accepts_a_moment_on_the_day_and_moments_before_and_after_it(): void
    {
        $this->assertTrue($this->passes([
            ['direction' => 'on', 'amount' => null, 'unit' => null],
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
            ['direction' => 'after', 'amount' => 2, 'unit' => 'weeks'],
            ['direction' => 'before', 'amount' => 3, 'unit' => 'months'],
        ]));
    }

    #[Test]
    public function it_refuses_a_rule_with_no_moment_at_all(): void
    {
        $this->assertFalse($this->passes([]));
        $this->assertFalse($this->passes('7 days before'));
    }

    #[Test]
    public function it_refuses_a_direction_it_does_not_know(): void
    {
        $this->assertFalse($this->passes([['direction' => 'roughly', 'amount' => 7, 'unit' => 'days']]));
        $this->assertFalse($this->passes([['direction' => '', 'amount' => 7, 'unit' => 'days']]));
    }

    #[Test]
    public function it_refuses_a_unit_it_does_not_know(): void
    {
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => 7, 'unit' => 'fortnights']]));
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => 7, 'unit' => null]]));
    }

    #[Test]
    public function it_refuses_a_moment_before_or_after_the_date_that_says_no_amount(): void
    {
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => null, 'unit' => 'days']]));
        $this->assertFalse($this->passes([['direction' => 'after', 'amount' => 'soon', 'unit' => 'days']]));
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => 1.5, 'unit' => 'days']]));
    }

    #[Test]
    public function it_refuses_an_amount_outside_what_a_moment_may_name(): void
    {
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => 0, 'unit' => 'days']]));
        $this->assertFalse($this->passes([['direction' => 'before', 'amount' => -7, 'unit' => 'days']]));
        $this->assertFalse($this->passes([['direction' => 'after', 'amount' => 366, 'unit' => 'days']]));
        $this->assertTrue($this->passes([['direction' => 'after', 'amount' => 365, 'unit' => 'days']]));
    }

    #[Test]
    public function it_refuses_two_moments_that_are_the_same(): void
    {
        $this->assertFalse($this->passes([
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
        ]));

        $this->assertFalse($this->passes([
            ['direction' => 'on', 'amount' => null, 'unit' => null],
            ['direction' => 'on', 'amount' => null, 'unit' => null],
        ]));
    }

    #[Test]
    public function it_accepts_the_same_amount_counted_in_a_different_unit(): void
    {
        $this->assertTrue($this->passes([
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
            ['direction' => 'before', 'amount' => 7, 'unit' => 'weeks'],
            ['direction' => 'after', 'amount' => 7, 'unit' => 'days'],
        ]));
    }

    #[Test]
    public function it_drops_the_amount_and_the_unit_from_a_moment_that_falls_on_the_day_itself(): void
    {
        $normalised = ReminderMoments::normalise([
            ['direction' => 'on', 'amount' => '7', 'unit' => 'days'],
            ['direction' => 'before', 'amount' => '7', 'unit' => 'days'],
        ]);

        $this->assertEquals([
            ['direction' => 'on', 'amount' => null, 'unit' => null],
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
        ], $normalised);
    }

    #[Test]
    public function it_treats_two_moments_on_the_day_as_the_same_whatever_amount_they_carry(): void
    {
        $this->assertFalse($this->passes([
            ['direction' => 'on', 'amount' => 7, 'unit' => 'days'],
            ['direction' => 'on', 'amount' => 3, 'unit' => 'weeks'],
        ]));
    }

    #[Test]
    public function it_says_what_went_wrong(): void
    {
        $validator = Validator::make(['moments' => []], ['moments' => [new ReminderMoments]]);

        $this->assertEquals(
            'A reminder needs at least one moment to tell you about.',
            $validator->errors()->first('moments'),
        );
    }

    private function passes(mixed $moments): bool
    {
        return Validator::make(['moments' => $moments], ['moments' => [new ReminderMoments]])->passes();
    }
}
