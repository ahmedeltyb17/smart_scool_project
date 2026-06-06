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
    Schema::create('parent_students', function (Blueprint $table) {
        $table->id();

        // أنواع الأعمدة متطابقة مع id في الجدول الأب
        $table->unsignedBigInteger('parent_id');
        $table->unsignedBigInteger('student_id');

        // إنشاء الـ Foreign Keys
        $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
        $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_students');
    }
};
