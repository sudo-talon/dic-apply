<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\DB;

class ApplicationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear the table before seeding
        DB::table('application_settings')->delete();

        // Create the default admission setting
        ApplicationSetting::create([
            'slug' => 'admission',
            'title' => 'Online Admission',
            'body' => 'Please fill out the form below to apply for admission.',
            'fee_amount' => 50,
            'pay_online' => 1,
            'status' => 1, // 1 for Active, 0 for Inactive
        ]);
    }
}