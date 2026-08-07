<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the user belongs to');
            $table->unsignedBigInteger('employee_id')->nullable()->index()->comment('employee the account gives access to, null when the account belongs to somebody who does not work for the company');
            $table->string('email')->unique()->comment('email address the user logs in with');
            $table->timestamp('email_verified_at')->nullable()->comment('when the email address was verified');
            $table->string('password_hash')->nullable()->comment('hashed password, null when the user signs in through SSO');
            $table->datetime('password_changed_at')->nullable()->comment('when the user last changed or set their password, null when the user signs in through SSO');
            $table->string('sso_provider')->nullable()->comment('SSO provider the user signs in with');
            $table->boolean('is_active')->default(true)->comment('whether the user can sign in, so a user can be suspended without being deleted');
            $table->string('locale', 5)->nullable()->comment('language of the interface, falls back to the company locale');
            $table->string('time_format', 2)->default('24')->comment('whether times are shown on a 24 hour or a 12 hour clock');
            $table->datetime('last_login_at')->nullable()->comment('when the user last signed in');
            $table->string('last_login_ip', 45)->nullable()->comment('ip address the user last signed in from, so a sign-in from somewhere new can be flagged');
            $table->text('two_factor_secret')->nullable()->comment('encrypted TOTP secret, null until the user enrols in two factor authentication');
            $table->timestamp('two_factor_confirmed_at')->nullable()->comment('when the user finished enrolling in two factor authentication, null while it is not in use');
            $table->text('two_factor_recovery_codes')->nullable()->comment('encrypted list of single use recovery codes for two factor authentication');
            $table->rememberToken()->comment('remember token');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary()->comment('email address of the user');
            $table->string('token')->comment('password reset token');
            $table->timestamp('created_at')->nullable()->comment('when the token was created');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('session id');
            $table->foreignId('user_id')->nullable()->index()->comment('user the session belongs to');
            $table->string('ip_address', 45)->nullable()->comment('ip address of the user');
            $table->text('user_agent')->nullable()->comment('user agent of the browser');
            $table->longText('payload')->comment('session payload');
            $table->integer('last_activity')->index()->comment('when the session was last active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropForeign(['owner_user_id']);
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
