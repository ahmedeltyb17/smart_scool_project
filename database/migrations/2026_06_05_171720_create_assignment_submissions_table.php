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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
            ->constrained('assignments')
            ->onDelete('cascade');

            $table->foreignId('student_id')
            ->constrained('students')
            ->onDelete('cascade');

            $table->string('file_path');

            $table->integer('score')->nullable();

            $table->text('feedback')->nullable();

            $table->enum('status', ['submitted', 'graded'])
            ->default('submitted');

        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
