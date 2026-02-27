<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->delete();

        $setting = Setting::create([

            'title'=>'University System',
            'meta_title'=>'University System',
            'logo_path'=>'dic-logo1.png',
            'favicon_path'=>'dic-logo1.png',
            'phone'=>'+880123456789',
            'email'=>'example@mail.com',
            'address'=>'Mirpur, Dhaka',
            'date_format'=>'d-m-Y',
            'time_format'=>'h:i A',
            'week_start'=>'1',
            'time_zone'=>'Asia/Dhaka',
            'currency'=>'USD',
            'currency_symbol'=>'$',
            'decimal_place'=>'2',
            'copyright_text'=> date('Y'). '- DIC - UNN PG | Designed By_ <a href="https://talongeeks.com/" target="_blank">Talongeeks</a>',
            'status'=>'1'

        ]);
    }
}
