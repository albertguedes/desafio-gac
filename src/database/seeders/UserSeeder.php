<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'josé da silva',
            'email' => 'jose@fakemail.com',
            'password' => Hash::make('jose@fakemail.com'),
        ]);

        User::factory(10)->create();
    }
}
