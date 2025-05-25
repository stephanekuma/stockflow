<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::query()->first();

        \App\Models\Store::factory()
            ->count(2)
            ->create()
            ->each(function ($store) use ($user) {
                $store->users()->attach($user->id);
            });
    }
}
