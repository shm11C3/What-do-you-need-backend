<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public const CATEGORIES = [
        ['uuid' => 'c056e80b-577e-d698-e8f1-2a94942f4f40', 'name' => 'test0'],
        ['uuid' => '7197ea30-7386-bd35-2130-edf52dd38f1f', 'name' => 'test1'],
        ['uuid' => '0b47a7ac-bfe6-3c91-86a2-d14710d3e17f', 'name' => 'test2'],
        ['uuid' => 'e6de46a9-b096-9db0-ff8b-3673207ab0da', 'name' => 'test3'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(self::CATEGORIES as $category){
            DB::table('categories')->insert([
                'uuid' => $category['uuid'],
                'name' => $category['name']
            ]);
        }
    }
}
