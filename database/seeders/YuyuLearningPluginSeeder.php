<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class YuyuLearningPluginSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('plugins')->whereRaw('LOWER(plugin_name) = ?', ['yuyulearning'])->exists()) {
            DB::table('plugins')->insert([
                'plugin_name' => 'Yuyulearning', 'plugin_name_full' => 'LMS',
                'display_flag' => 1, 'display_sequence' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
