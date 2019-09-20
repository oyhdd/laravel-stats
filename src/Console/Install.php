<?php

namespace Oyhdd\StatsCenter\Console;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'stats:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the stats-center package';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->initDatabase();
    }

    /**
     * Create tables and seed it.
     *
     * @return void
     */
    public function initDatabase()
    {
        $this->call('migrate', ['--path' => './packages/stats-center/database/migrations/']);

        $this->call('db:seed', ['--class' => \Oyhdd\StatsCenter\Models\AdminMenuTableSeeder::class]);
    }
}
