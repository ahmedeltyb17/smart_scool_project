<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
   public function up(): void
{
    DB::statement('ALTER TABLE attendances DROP INDEX attendances_student_id_class_id_unique');

    DB::statement('ALTER TABLE attendances 
        ADD UNIQUE attendances_student_class_date_unique (student_id, class_id, date)');
}

    public function down(): void
{
    DB::statement('ALTER TABLE attendances DROP INDEX attendances_student_class_date_unique');

    DB::statement('ALTER TABLE attendances 
        ADD UNIQUE attendances_student_id_class_id_unique (student_id, class_id)');
}
};