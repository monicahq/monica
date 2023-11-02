<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::where('locale', 'no')->update(['locale' => 'nn']);
        User::where('locale', 'zh')->update(['locale' => 'zh_CN']);
    }
};
