<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id_card_settings - prefix already exists, so we skip it safely
        Schema::table('id_card_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('id_card_settings', 'prefix')) {
                $table->string('prefix')->nullable();
            }
        });

        // print_settings - prefix may or may not exist
        Schema::table('print_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('print_settings', 'prefix')) {
                $table->string('prefix')->nullable();
            }
        });

        // students table - these fields are new
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'school_transcript')) {
                $table->string('school_transcript')->nullable();
            }
            if (!Schema::hasColumn('students', 'school_certificate')) {
                $table->string('school_certificate')->nullable();
            }
            if (!Schema::hasColumn('students', 'collage_transcript')) {
                $table->string('collage_transcript')->nullable();
            }
            if (!Schema::hasColumn('students', 'collage_certificate')) {
                $table->string('collage_certificate')->nullable();
            }
        });

        // applications table
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'school_transcript')) {
                $table->string('school_transcript')->nullable();
            }
            if (!Schema::hasColumn('applications', 'school_certificate')) {
                $table->string('school_certificate')->nullable();
            }
            if (!Schema::hasColumn('applications', 'collage_transcript')) {
                $table->string('collage_transcript')->nullable();
            }
            if (!Schema::hasColumn('applications', 'collage_certificate')) {
                $table->string('collage_certificate')->nullable();
            }
            if (!Schema::hasColumn('applications', 'fee_amount')) {
                $table->double('fee_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('applications', 'pay_status')) {
                $table->tinyInteger('pay_status')->default(0)
                      ->comment('0 Unpaid, 1 Paid, 2 Cancel');
            }
            if (!Schema::hasColumn('applications', 'payment_method')) {
                $table->integer('payment_method')->nullable();
            }
        });

        // application_settings table
        Schema::table('application_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('application_settings', 'fee_amount')) {
                $table->double('fee_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('application_settings', 'pay_online')) {
                $table->boolean('pay_online')->default(1)
                      ->comment('0 No, 1 Yes');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('id_card_settings', function (Blueprint $table) {
            if (Schema::hasColumn('id_card_settings', 'prefix')) {
                $table->dropColumn('prefix');
            }
        });

        Schema::table('print_settings', function (Blueprint $table) {
            if (Schema::hasColumn('print_settings', 'prefix')) {
                $table->dropColumn('prefix');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $columns = ['school_transcript', 'school_certificate', 'collage_transcript', 'collage_certificate'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('applications', function (Blueprint $table) {
            $columns = ['school_transcript', 'school_certificate', 'collage_transcript', 'collage_certificate', 'fee_amount', 'pay_status', 'payment_method'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('application_settings', function (Blueprint $table) {
            $columns = ['fee_amount', 'pay_online'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('application_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};