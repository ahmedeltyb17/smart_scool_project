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
    Schema::table('schedules', function (Blueprint $table) {

        $table->foreignId('teacher_id')
            ->after('id')
            ->constrained('teachers')
            ->cascadeOnDelete();

    });
}

public function down(): void
{
    Schema::table('schedules', function (Blueprint $table) {

        $table->dropForeign(['teacher_id']);
        $table->dropColumn('teacher_id');

    });
}
};
