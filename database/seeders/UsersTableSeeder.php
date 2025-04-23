<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crea utente admin di default
        User::create([
            'name' => 'Admin',
            'email' => 'admin@bec.it',
            'password' => Hash::make('password'),
        ]);
        
        // Aggiungi altri utenti se necessario
        User::create([
            'name' => 'Operatore',
            'email' => 'operatore@bec.it',
            'password' => Hash::make('password'),
        ]);
    }
}