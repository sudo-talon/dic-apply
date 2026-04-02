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
        Schema::table('students', function (Blueprint $table) {
            // Add columns for Present Address
            $table->unsignedBigInteger('present_country')->nullable()->after('present_address');
            $table->unsignedBigInteger('present_province')->nullable()->after('present_country');
            $table->unsignedBigInteger('present_district')->nullable()->after('present_province');

            // Add columns for Permanent Address
            $table->unsignedBigInteger('permanent_country')->nullable()->after('permanent_address');
            $table->unsignedBigInteger('permanent_province')->nullable()->after('permanent_country');
            $table->unsignedBigInteger('permanent_district')->nullable()->after('permanent_province');
        });
    }

    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $columns = [
            'present_country',
            'present_province',
            'present_district',
            'permanent_country',
            'permanent_province',
            'permanent_district',
        ];

        // Drop columns only if they exist
        foreach ($columns as $column) {
            if (Schema::hasColumn('students', $column)) {
                $table->dropColumn($column);
            }
        }
    });
}
};