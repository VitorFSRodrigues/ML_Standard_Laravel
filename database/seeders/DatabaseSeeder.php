<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([ClientesSeeder::class]);
        $this->call([PerguntasSeeder::class]);
        $this->call([
            OrcamentistasSeeder::class,
            ConferenteOrcamentistaSeeder::class,
            ConferenteComercialSeeder::class,
        ]);
        $this->call(PipedriveSampleSeeder::class);
        $this->call(OrcMLstdSeeder::class);
        $this->call(StdELESeeder::class);
        $this->call(StdTUBSeeder::class);
        $this->call(DictSeeder::class);
        $this->call(MlFeedbackSamplesSeeder::class);
        $this->call(ModelosMlSeeder::class);
    }
}

