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
            $table->unsignedBigInteger('account_id')->comment('account the user belongs to');
            $table->string('first_name')->comment('first name of the user');
            $table->string('last_name')->comment('last name of the user');
            $table->string('photo_path')->nullable()->comment('path of the photo file');
            $table->string('email')->unique()->comment('email address the user logs in with');
            $table->timestamp('email_verified_at')->nullable()->comment('when the email address was verified');
            $table->string('password_hash')->nullable()->comment('hashed password, null when the user signs in through SSO');
            $table->datetime('password_changed_at')->nullable()->comment('when the user last changed or set their password, null when the user signs in through SSO');
            $table->string('sso_provider')->nullable()->comment('SSO provider the user signs in with');
            $table->boolean('is_active')->default(true)->comment('whether the user can sign in, so a user can be suspended without being deleted');
            $table->string('locale', 5)->nullable()->comment('language of the interface, falls back to the language of the application');
            $table->string('time_format', 2)->default('24')->comment('whether times are shown on a 24 hour or a 12 hour clock');
            $table->datetime('last_login_at')->nullable()->comment('when the user last signed in');
            $table->string('last_login_ip', 45)->nullable()->comment('ip address the user last signed in from, so a sign-in from somewhere new can be flagged');
            $table->text('two_factor_secret')->nullable()->comment('encrypted TOTP secret, null until the user enrols in two factor authentication');
            $table->timestamp('two_factor_confirmed_at')->nullable()->comment('when the user finished enrolling in two factor authentication, null while it is not in use');
            $table->text('two_factor_recovery_codes')->nullable()->comment('encrypted list of single use recovery codes for two factor authentication');
            $table->datetime('last_saved_at')->nullable()->comment('when the user last saved their own profile, shown on that screen');
            $table->rememberToken()->comment('remember token');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
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
};
