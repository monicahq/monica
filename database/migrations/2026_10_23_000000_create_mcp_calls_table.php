<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_calls', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier of the call shown on the history screen and by the API');
            $table->foreignIdFor(Account::class)->comment('account the call was made on')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->comment('user whose connection made the call')->constrained()->cascadeOnDelete();
            $table->string('connection_type')->comment('api_key or oauth_client, see McpConnectionTypeEnum');
            $table->string('connection_id')->comment('Sanctum token id or Passport client id, without a foreign key so the row outlives a revocation');
            $table->string('connection_name')->comment('name of the connection when the call was made');
            $table->string('tool')->comment('tool name as the client sent it, such as get-contact');
            $table->string('operation')->nullable()->comment('read, create, update or delete, from the mirrored API route, null for an unknown tool');
            $table->foreignIdFor(Vault::class)->nullable()->comment('vault the call reached, only when found inside the account')->constrained()->nullOnDelete();
            $table->string('vault_name')->nullable()->comment('name of the vault when the call was made');
            $table->string('subject_type')->nullable()->comment('morph class of the record the call reached');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('morph key of the record the call reached, no foreign key because the row outlives the record');
            $table->string('subject_label')->nullable()->comment('name of the record when the call was made');
            $table->string('result')->comment('succeeded, refused, invalid_parameters, error or interrupted, see McpCallResultEnum');
            $table->integer('error_code')->nullable()->comment('JSON-RPC error code, when there is one');
            $table->text('error_message')->nullable()->comment('reason sent to the client, when there is one');
            $table->text('parameters')->nullable()->comment('parameters received, masked, as JSON of at most 16 384 bytes');
            $table->boolean('parameters_truncated')->default(false)->comment('whether the parameters were cut to fit');
            $table->text('response')->nullable()->comment('response returned, masked, as JSON of at most 16 384 bytes');
            $table->boolean('response_truncated')->default(false)->comment('whether the response was cut to fit');
            $table->string('json_rpc_id')->nullable()->comment('JSON-RPC identifier the client sent, as a string');
            $table->string('ip_address', 45)->nullable()->comment('address the request came from');
            $table->dateTime('started_at', precision: 3)->comment('UTC moment the call started, to the millisecond');
            $table->unsignedInteger('duration_in_milliseconds')->nullable()->comment('how long the call took, null while interrupted');
            $table->timestamps(precision: 3);

            $table->index(['account_id', 'started_at']);
            $table->index(['account_id', 'result']);
            $table->index(['account_id', 'tool']);
            $table->index(['account_id', 'connection_type', 'connection_id']);
        });
    }
};
