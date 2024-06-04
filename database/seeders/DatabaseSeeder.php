<?php

namespace Database\Seeders;

use App\V1\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'IDRole'        => 1,
            'IDBranch'      => 1,
            'UserName'      => 'Admin',
            'UserEmail'     => 'admin@gmail.com',
            'UserPhone'     => '01159747840',
            'UserPassword'  => Hash::make('123456789'),
        ]);
        // \App\Models\User::factory(10)->create();
    }
}
