<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service';
    protected $description = 'Create a new service class';

       public function handle()
    {
        $name = $this->argument('name');
        $path = app_path('Services/' . $name . '.php');

        if (file_exists($path)) {
            $this->error('Service already exists!');
            return;
        }

        $stub = <<<EOD
        <?php

        namespace App\Services;

        class $name
        {
            public function __construct()
            {
                // Constructor logic
            }
        }
        EOD;

        file_put_contents($path, $stub);
        $this->info("Service {$name} created successfully!");
    }
}
