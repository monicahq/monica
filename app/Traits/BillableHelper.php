<?php

namespace App\Traits;

use Laravel\Cashier\Billable;

trait BillableHelper {
    use Billable;

    private $mocked = false;
    private $testData;

    /**
     * @param array $testData
     */
    public function fake($testData) {
        $this->mocked = true;
        $this->testData = $testData;
    }

    public function asStripeCustomer() {
        if (!$this->mocked) {
            return parent::asStripeCustomer();
        }
        return [
            'subscriptions' => (object)[
                'data' => [[
                    'current_period_end' => $this->testData['next_billing_date']
                ]]
            ]
        ];
    }
}