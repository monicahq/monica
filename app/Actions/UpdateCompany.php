<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SizeRangeEnum;
use App\Enums\UserActionEnum;
use App\Enums\WorkModeEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Update the information of a company. Only a member of the company may do so.
 */
class UpdateCompany
{
    public function __construct(
        private readonly User $user,
        private readonly Company $company,
        private string $name,
        private ?string $legalName = null,
        private ?string $websiteUrl = null,
        private ?string $industry = null,
        private readonly ?SizeRangeEnum $sizeRange = null,
        private readonly ?WorkModeEnum $workMode = null,
        private readonly string $timezone = 'UTC',
        private readonly string $locale = 'en',
        private readonly ?string $currency = null,
        private ?string $billingEmail = null,
    ) {}

    public function execute(): Company
    {
        $this->validate();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->company;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->user,
            action: UserActionEnum::CompanyUpdate,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->company->id) {
            throw new ModelNotFoundException('Company not found');
        }
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->legalName = TextSanitizer::nullablePlainText($this->legalName);
        $this->websiteUrl = TextSanitizer::nullablePlainText($this->websiteUrl);
        $this->industry = TextSanitizer::nullablePlainText($this->industry);
        $this->billingEmail = TextSanitizer::nullablePlainText($this->billingEmail);
    }

    private function update(): void
    {
        $this->company->name = $this->name;
        $this->company->legal_name = $this->legalName;
        $this->company->website_url = $this->websiteUrl;
        $this->company->industry = $this->industry;
        $this->company->size_range = $this->sizeRange;
        $this->company->work_mode = $this->workMode;
        $this->company->timezone = $this->timezone;
        $this->company->locale = $this->locale;
        $this->company->currency = $this->currency;
        $this->company->billing_email = $this->billingEmail;
        $this->company->save();
    }
}
