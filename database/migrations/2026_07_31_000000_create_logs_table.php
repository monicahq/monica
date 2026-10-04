<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the action was performed in')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vault_id')->nullable()->comment('vault the action touched, null for account level actions');
            $table->foreignIdFor(User::class)->nullable()->comment('user who performed the action, null once that user is deleted and null when the system acted on its own')->constrained()->nullOnDelete();
            $table->string('user_email')->comment('email address of the user at the time of the action, kept so the log still says who acted once the user is gone, or a marker when the system acted');
            $table->string('action')->comment('action that was performed, also used as translation key');
            $table->json('parameters')->nullable()->comment('parameters for the translation');
            $table->string('loggable_type')->nullable()->comment('class of the object acted on, null when the action targets nothing in particular');
            $table->unsignedBigInteger('loggable_id')->nullable()->comment('id of the object acted on, carrying no foreign key since the log outlives its subject');
            $table->json('changes')->nullable()->comment('the fields that changed, each with its value before and after');
            $table->string('source', 20)->default('web')->comment('interface the action came from');
            $table->string('source_detail')->nullable()->comment('which token, module or import run precisely, null when there is nothing to name');
            $table->string('ip_address', 45)->nullable()->comment('address the action came from, null when the system acted');
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['loggable_type', 'loggable_id']);
        });
    }
};
