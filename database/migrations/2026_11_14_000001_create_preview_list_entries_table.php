<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preview_list_entries', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('email')->unique()->comment('address waiting for an account, stored lowercased');
            $table->string('locale')->comment('language the visitor read the page in, used for the email');
            $table->string('token', 64)->nullable()->unique()->comment('hash of the token in the admission link, null before an admission and once used');
            $table->datetime('token_expires_at')->nullable()->comment('when the admission link stops working');
            $table->datetime('admitted_at')->nullable()->comment('when an administrator last admitted the address');
            $table->datetime('registered_at')->nullable()->comment('when the account was created through the link');
            $table->timestamps();
        });
    }
};
