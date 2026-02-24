<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\SMSSetting;

class SMSSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('s_m_s_settings')->delete();

        $s_m_s_settings = SMSSetting::create([

            'nexmo_key'=>'your-nexmo-key',
            'nexmo_secret'=>'your-nexmo-secret',
            'nexmo_sender_name'=>'your-sender',
            'twilio_sid'=>'twilio_sid_placeholder',
            'twilio_auth_token'=>'twilio_auth_token_placeholder',
            'twilio_number'=>'+10000000000',
            'status'=>'1',

        ]);
    }
}
