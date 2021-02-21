<?php

use App\Models\Account\Company;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->after('company')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // migrate all company strings to Company object
        // we also make sure that we don’t create duplicates when creating new
        // companies Objects
        Contact::whereNotNull('company')->select('id', 'company', 'account_id')->chunk(100, function ($contacts) {
            $contacts->each(function (Contact $contact) {
                $companyName = $contact->company;

                $existingCompanyObject = Company::where('account_id', $contact->account_id)
                    ->where('name', $companyName)
                    ->first();

                if ($existingCompanyObject) {
                    $companyId = $existingCompanyObject->id;
                } else {
                    $company = Company::create([
                        'account_id' => $contact->account_id,
                        'name' => $companyName,
                    ]);
                    $companyId = $company->id;
                }

                Contact::where('id', $contact->id)->update([
                    'company_id' => $companyId,
                ]);
            });
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
}
