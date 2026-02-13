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
        $this->call(OrcamentistasSeeder::class);
        $this->call(OrcMLstdSeeder::class);
        $this->call(StdELESeeder::class);
        $this->call(StdTUBSeeder::class);
        $this->call(DictSeeder::class);
        $this->call(MlFeedbackSamplesSeeder::class);
        $this->call(ModelosMlSeeder::class);
        $this->call(EvidenciasUsoMlSeeder::class);
    }
}

