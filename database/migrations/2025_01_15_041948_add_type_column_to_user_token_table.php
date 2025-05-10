<?php

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
        Schema::table('user_tokens', function (Blueprint $table) {
            $table->string('type')->nullable()->after('user_id'); // Replace 'column_name' with the name of the column you want to add this column after.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
