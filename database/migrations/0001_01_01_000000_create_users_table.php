<?php

declare(strict_types=1);

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->unique()->comment('account the user belongs to, unique because an account holds exactly one person')->constrained()->cascadeOnDelete();
            $table->string('first_name')->comment('first name of the user');
            $table->string('last_name')->comment('last name of the user');
            $table->string('photo_path')->nullable()->comment('path of the photo file');
            $table->string('email')->unique()->comment('email address the user logs in with');
            $table->timestamp('email_verified_at')->nullable()->comment('when the email address was verified');
            $table->string('password')->nullable()->comment('hashed password, null when the user signed up through SSO or a passkey');
            $table->datetime('password_changed_at')->nullable()->comment('when the user last changed or set their password, null while they have none');
            $table->boolean('is_active')->default(true)->comment('whether the user can sign in, so an account can be suspended without being deleted');
            $table->string('locale', 12)->nullable()->comment('language of the interface, falls back to the language of the application');
            $table->string('timezone', 64)->default('UTC')->comment('IANA time zone every date is rendered in, and the one a new reminder rule is frozen with');
            $table->string('time_format', 2)->default('24')->comment('whether times are shown on a 24 hour or a 12 hour clock');
            $table->string('date_format', 20)->nullable()->comment('order the parts of a date read in, null to follow the locale');
            $table->string('name_order', 20)->default('given_first')->comment('order the name reads in, which the locale cannot always decide');
            $table->string('number_format', 20)->nullable()->comment('separators used for numbers, null to follow the locale');
            $table->string('contact_list_grouping', 20)->nullable()->comment('grouping the people list was last looked at with, null until the user has looked at it');
            $table->string('contact_list_sort', 20)->nullable()->comment('field the people list was last sorted by, null until the user has looked at it');
            $table->string('contact_list_direction', 4)->nullable()->comment('direction the people list was last sorted in, null until the user has looked at it');
            $table->boolean('uses_readable_addresses')->default(true)->comment('whether the product builds addresses naming a vault and a contact, rather than addresses carrying their unguessable identifiers');
            $table->datetime('last_login_at')->nullable()->comment('when the user last signed in');
            $table->string('last_login_ip', 45)->nullable()->comment('ip address the user last signed in from, so a sign-in from somewhere new can be flagged');
            $table->text('two_factor_secret')->nullable()->comment('encrypted TOTP secret, null until the user enrols in two factor authentication');
            $table->timestamp('two_factor_confirmed_at')->nullable()->comment('when the user finished enrolling in two factor authentication, null while it is not in use');
            $table->text('two_factor_recovery_codes')->nullable()->comment('encrypted list of single use recovery codes for two factor authentication');
            $table->unsignedBigInteger('two_factor_last_timestep')->nullable()->comment('time step of the last authenticator code accepted, so the same code cannot be used twice');
            $table->datetime('last_saved_at')->nullable()->comment('when the user last saved their own profile, shown on that screen');
            $table->datetime('welcomed_at')->nullable()->comment('when the user pressed Let me in on the welcome screen, null until then');
            $table->datetime('full_access_offer_dismissed_at')->nullable()->comment('when the user hid the banner offering full access, null until then');
            $table->rememberToken()->comment('remember token');
            $table->timestamps();
            $table->softDeletes();
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
