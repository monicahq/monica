<?php

use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChangeGiftStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('gifts', 'status')) {
            if (Schema::hasColumn('gifts', 'date')) {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->dropColumn(['status', 'date']);
                });
            } else {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        } elseif (Schema::hasColumn('gifts', 'date')) {
            Schema::table('gifts', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }

        Gift::chunk(500, function ($gifts) {
            foreach ($gifts as $gift) {
                try {
                    Contact::findOrFail($gift->is_for);
                } catch (ModelNotFoundException $e) {
                    $gift->recipient = null;
                    $gift->save();
                }
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('is_for')->nullable()->change();
            $table->string('status', 8)->after('has_been_received')->default('idea');
            $table->datetime('date')->after('status')->nullable();

            $table->foreign('is_for')->references('id')->on('contacts')->onDelete('set null');
        });

        Gift::chunk(500, function ($gifts) {
            foreach ($gifts as $gift) {
                $gift->status = $gift->has_been_offered === 1 ? 'offered' :
                        ($gift->has_been_received === 1 ? 'received' : 'idea');
                $gift->save();
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn([
                'is_an_idea',
                'has_been_offered',
                'has_been_received',
                'offered_at',
                'received_at',
            ]);
        });
    }
}
