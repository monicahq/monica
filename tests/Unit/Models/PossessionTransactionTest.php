<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PossessionTransaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionTest extends TestCase
{
    #[Test]
    public function its_total_is_the_sum_of_the_figures_set(): void
    {
        $transaction = new PossessionTransaction(['amount' => '40.00', 'tax' => '3.20', 'shipping' => '6.00']);

        $this->assertEquals('49.20', $transaction->total());
        $this->assertEquals('sum', $transaction->totalSource());
    }

    #[Test]
    public function a_total_typed_in_wins_over_the_sum(): void
    {
        $transaction = new PossessionTransaction(['amount' => '40.00', 'tax' => '3.20', 'entered_total' => '45.00']);

        $this->assertEquals('45.00', $transaction->total());
        $this->assertEquals('entered', $transaction->totalSource());
    }

    #[Test]
    public function without_any_figure_its_total_is_unknown(): void
    {
        $transaction = new PossessionTransaction;

        $this->assertNull($transaction->total());
        $this->assertEquals('unknown', $transaction->totalSource());
    }
}
