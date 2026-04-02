<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
      /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Define the columns to add and the column to place them after
            $columns = [
                'present_village' => ['type' => 'string'],
                'present_address' => ['type' => 'text'],
                'permanent_village' => ['type' => 'string'],
                'permanent_address' => ['type' => 'text'],
                'present_country' => ['type' => 'integer'],
                'present_province' => ['type' => 'integer'],
                'present_district' => ['type' => 'integer'],
                'permanent_country' => ['type' => 'integer'],
                'permanent_province' => ['type' => 'integer'],
                'permanent_district' => ['type' => 'integer'],
            ];

            $after = 'mother_occupation'; // The column to add new columns after

            foreach ($columns as $name => $properties) {
                if (!Schema::hasColumn('applications', $name)) {
                    $column = $table->{$properties['type']}($name)->nullable()->after($after);
                }
                // Update the 'after' for the next column in the loop
                $after = $name;
            }
        });
    }
        /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $columns_to_drop = [
                'present_village',
                'present_address',
                'permanent_village',
                'permanent_address',
                'present_country',
                'present_province',
                'present_district',
                'permanent_country',
                'permanent_province',
                'permanent_district',
            ];

            foreach ($columns_to_drop as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
