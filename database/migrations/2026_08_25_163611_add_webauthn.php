<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webauthn_keys', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();

            $table->string('name')->default('key');
            $table->mediumText('credentialId');
            $table->string('type', 255);
            $table->text('transports');
            $table->string('attestationType', 255);
            $table->text('trustPath');
            $table->text('aaguid');
            $table->text('credentialPublicKey');
            $table->bigInteger('counter')->unsigned();
            $table->timestamps();
            $table->timestamp('used_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique, not merely indexed. Nothing above the database refuses a
            // second row for the same credential: the browser declines one
            // through excludeCredentials, and web-auth/webauthn-lib does not
            // check during attestation. FR-004 says no duplicate is created, and
            // a guarantee that only holds while the caller is a cooperating
            // browser is not one.
            //
            // MySQL needs a prefix length on a mediumtext. 255 characters is far
            // longer than any credential id a real authenticator produces, so a
            // unique prefix is unique in practice.
            if (app(Resolver::class)->connection($this->getConnection()) instanceof MySqlConnection) {
                $table->unique([app('db')->raw('credentialId(255)')], 'credential_index');
            } else {
                $table->unique('credentialId', 'credential_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webauthn_keys');
    }
};
