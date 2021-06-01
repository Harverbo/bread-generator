<?php

namespace Harverbo\BreadGenerator\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BreadStubGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bread:make {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all required stubs for a new Laravel Voyager BREAD';

    /**
     * Dependencies
     */
    protected $artisan;

    /**
     * Global variables
     */
    private $slug = '';
    private $slug_plural = '';
    private $title = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Artisan $artisan)
    {
        parent::__construct();

        $this->artisan = $artisan;
    }

    /**
     * Initialize necessary variables.
     *
     * @return void
     */
    public function initializeVariables()
    {
        // Create the BREAD slug
        $this->slug = Str::snake($this->argument('name'));

        // Get the plural of the slug
        $this->slug_plural = Str::plural($this->slug);

        // Get the title of the slug
        $this->title = str_replace('_', ' ', Str::title($this->slug));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Initialize variables
        $this->initializeVariables();

        // Generate model
        $this->generateModel();

        // Generate controller
        $this->generateController();

        // Generate resources
        $this->generateResources();

        // Update routes
        $this->updateRoutes();

        // Give feedback
        $this->info('All BREAD stubs generated successfully!');
    }

    /**
     * Generate model file
     *
     * @return void
     */
    private function generateModel()
    {
        // Get the file name
        $filename = app_path() . '/' . $this->argument('name') . '.php';

        // Process if model doesn't exist
        if (!file_exists($filename)) {
            // Give feedback
            $this->info('Creating model.');

            // Get model stub
            $model = file_get_contents(__DIR__ . '/../../app/Model.stub');

            // Replace placeholders
            $new_model = str_replace('{{BREAD}}', $this->argument('name'), $model);
            $new_model = str_replace('{{SLUG_PLURAL}}', $this->slug_plural, $new_model);

            // Create model
            file_put_contents($filename, $new_model);
        }
    }

    /**
     * Generate controller file
     *
     * @return void
     */
    private function generateController()
    {
        // Get the file name
        $filename = app_path('Http/Controllers') . '/' . $this->argument('name') . 'Controller.php';

        // Process if controller doesn't exist
        if (!file_exists($filename)) {
            // Give feedback
            $this->info('Creating controller.');

            // Get controller stub
            $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/controller.stub');

            // Replace placeholders
            $new_controller = str_replace('{{BREAD}}', $this->argument('name'), $controller);
            $new_controller = str_replace('{{SLUG}}', $this->slug, $new_controller);

            // Create controller
            file_put_contents($filename, $new_controller);
        }
    }

    /**
     * Generate resource files
     *
     * @return void
     */
    private function generateResources()
    {
        // Get the file names
        $browse_filename = resource_path('views/vendor/voyager/' . $this->slug . '/browse.blade.php');
        $read_filename = resource_path('views/vendor/voyager/' . $this->slug . '/read.blade.php');
        $edit_add_filename = resource_path('views/vendor/voyager/' . $this->slug . '/edit-add.blade.php');

        // Process if controller doesn't exist
        if (!file_exists($browse_filename) || !file_exists($read_filename) || !file_exists($edit_add_filename)) {
            // Give feedback
            $this->info('Creating views.');

            // Create directory if doesn't exist
            if (!is_dir(resource_path('views/vendor/voyager/' . $this->slug))) {
                mkdir(resource_path('views/vendor/voyager/' . $this->slug), 0777, true);
            }

            // Create browse view
            if (!file_exists($browse_filename)) {

                // Get browse stub
                $browse = file_get_contents(__DIR__ . '/../../resources/views/vendor/voyager/slug/browse.blade.stub');

                // Replace placeholders
                $new_browse = str_replace('{{SLUG}}', $this->slug, $browse);

                // Create browse
                file_put_contents($browse_filename, $new_browse);
            }

            // Create read view
            if (!file_exists($read_filename)) {
                // Get read stub
                $read = file_get_contents(__DIR__ . '/../../resources/views/vendor/voyager/slug/read.blade.stub');

                // Create read
                file_put_contents($read_filename, $read);
            }

            // Create edit_add view
            if (!file_exists($edit_add_filename)) {
                // Get edit_add stub
                $edit_add = file_get_contents(__DIR__ . '/../../resources/views/vendor/voyager/slug/edit-add.blade.stub');

                // Create edit_add
                file_put_contents($edit_add_filename, $edit_add);
            }
        }
    }

    /**
     * Update web route file
     *
     * @return void
     */
    private function updateRoutes()
    {
        // Give feedback
        $this->info('Updating web routes.');

        // Get route stub
        $route = file_get_contents(__DIR__ . '/../../routes/web.stub');

        // Replace placeholders
        $new_route = str_replace('{{SLUG}}', $this->slug, $route);
        $new_route = str_replace('{{SLUG_PLURAL}}', $this->slug_plural, $new_route);
        $new_route = str_replace('{{BREAD_PATH}}', 'App\\Http\\Controllers\\' . $this->argument('name'), $new_route);

        // Create route
        file_put_contents(base_path('routes/') . 'web.php', $new_route, FILE_APPEND);
    }
}
