<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the employee works for');
            $table->string('employee_number')->nullable()->comment('identifier of the employee in the payroll or hr systems of the company');
            $table->string('first_name')->comment('first name of the employee');
            $table->string('last_name')->comment('last name of the employee');
            $table->string('display_name')->nullable()->comment('name the employee goes by, when it differs from their legal name');
            $table->string('photo_path')->nullable()->comment('path of the photo file');
            $table->string('work_email')->nullable()->comment('email address the employee uses at work');
            $table->string('custom_title')->nullable()->comment('free text job title, when the employee needs one that no official job title covers');
            $table->string('country', 2)->nullable()->comment('country the employee works from, as an iso 3166-1 alpha-2 code, independent from the office they belong to');
            $table->string('timezone', 50)->nullable()->comment('timezone the employee works in, falls back to the company timezone');
            $table->date('hired_at')->nullable()->comment('date the employee joined, or is expected to join, the company');
            $table->date('departed_at')->nullable()->comment('date the employee left, or is expected to leave, the company');
            $table->string('personal_email')->nullable()->comment('personal email address of the employee');
            $table->string('personal_phone')->nullable()->comment('personal phone number of the employee');
            $table->date('date_of_birth')->nullable()->comment('date of birth of the employee');
            $table->string('emergency_contact_name')->nullable()->comment('name of the person to contact in case of emergency');
            $table->string('emergency_contact_phone')->nullable()->comment('phone number of the person to contact in case of emergency');
            $table->string('emergency_contact_relationship')->nullable()->comment('how the emergency contact relates to the employee');
            $table->text('home_address')->nullable()->comment('home address of the employee');
            $table->datetime('last_saved_at')->nullable()->comment('when the employee last saved their own information, shown on their profile');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'employee_number']);
            $table->unique(['company_id', 'work_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
