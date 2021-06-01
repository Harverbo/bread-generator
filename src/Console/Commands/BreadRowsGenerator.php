<?php

namespace Harverbo\BreadGenerator\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BreadRowsGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bread:rows {name} {--i|icon=voyager-bread : The voyager icon for the BREAD} {--s|singular=default : The singular name for the resource} {--p|plural=default : The plural name for the resource} {--o|order=name : The column user for the sorting} {--x|sort=asc : The sorting type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all required rows data and seeders for the stub';

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
    private $fields = [];
    private $rows = [];

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
    { // Initialize variables
        $this->initializeVariables();

        // Handle user inputs
        $this->handleUserInputs();

        // Generate migration
        $this->generateMigration();

        // Update migration
        $this->updateMigration();

        // Generate seeders
        $this->generateSeeder();

        // Update seeders
        $this->updateSeeder();

        // Update database seeder
        $this->updateDatabaseSeeder();

        // Generate requests
        $this->generateRequests();
    }

    /**
     * Handle user inputs
     *
     * @return mixed
     */
    private function handleUserInputs()
    {
        // Fields
        while ($this->confirm('¿Desea agregar un campo?')) {
            // Field name
            $name = $this->ask('Indica el nombre del campo', 'name');

            // Field type
            $type = $this->choice(
                'Indica el tipo del campo',
                [
                    'varchar',
                    'tiny_integer',
                    'integer',
                    'decimal',
                    'boolean',
                    'timestamp',
                    'relationship',
                ],
                0,
            );

            // Extra values if field is relationship
            $relationship_field = null;
            $relationship_table = null;
            $relationship_cascade = null;
            $relationship_model = null;
            $relationship_label = null;
            if ($type == 'relationship') {
                $relationship_field = $this->ask('¿A qué campo hace referencia?', 'id');
                $relationship_table = $this->ask('¿A qué tabla hace referencia?', 'users');
                $relationship_cascade = $this->confirm('¿Borrado en cascada?');
                $relationship_model = $this->ask('Indique el model a usar en la relación', ucfirst(Str::camel(Str::singular($relationship_table))));
                $relationship_label = $this->ask('Indique la etiqueta a usar para mostrar el valor de la relación', 'name');
            }

            // Field default value
            $default = null;
            if ($this->confirm('¿Tiene valor por defecto?')) {
                $default = $this->ask('Indica el valor por defecto');
            }

            // Field nullable
            $nullable = $this->confirm('¿Puede ser nulo?');

            // Fill fields
            $this->fields[$name] = (object) [
                'name' => $name,
                'type' => $type,
                'default' => $default,
                'nullable' => $nullable,
                'relationship_field' => $relationship_field,
                'relationship_table' => $relationship_table,
                'relationship_cascade' => $relationship_cascade,
                'relationship_model' => $relationship_model,
                'relationship_label' => $relationship_label,
            ];
        }

        // Handle keys loop
        $key = 0;

        // Loop through each field to select row type
        foreach ($this->fields as $field) {
            $key = $key + 2;

            // Field type
            $type = $this->choice(
                'Indica el tipo del campo ' . $field->name,
                [
                    'text',
                    'rich_textbox',
                    'number',
                    'boolean',
                    'select',
                    'relationship',
                    'date',
                    'datetime',
                ],
                0,
            );

            // Field name
            $name = $this->ask('Indica el nombre del campo', 'Nombre');

            // Field permissions
            $permissions = $this->choice(
                'Selecciona los permisos del campo separados por coma',
                [
                    'required',
                    'browse',
                    'read',
                    'edit',
                    'add',
                    'delete',
                    'none',
                ],
                '0,1,2,3,4,5',
                null,
                true
            );

            // Fill rows
            $this->rows[$name] = (object) [
                'key' => $key,
                'field' => $field->name,
                'type' => $type,
                'name' => $name,
                'permissions' => $permissions,
            ];
        }
    }

    /**
     * Generate migration file
     *
     * @return void
     */
    private function generateMigration()
    {
        // Give feedback
        $this->info('Creating migration.');

        $this->artisan::call('make:migration', [
            'name' => $this->slug_plural,
            '--create' => $this->slug_plural
        ]);
    }

    /**
     * Update migration file
     *
     * @return void
     */
    private function updateMigration()
    {
        // Give feedback
        $this->info('Updating migration.');

        // Delete migration
        $this->deleteMigration();

        // New fields
        $fields = "";

        // Assemble new migration fields
        foreach ($this->fields as $field) {
            if ($field->type == 'varchar') {
                $fields .= '$table->string("';
            } elseif ($field->type == 'tiny_integer') {
                $fields .= '$table->tinyInteger("';
            } elseif ($field->type == 'integer') {
                $fields .= '$table->integer("';
            } elseif ($field->type == 'decimal') {
                $fields .= '$table->decimal("';
            } elseif ($field->type == 'boolean') {
                $fields .= '$table->boolean("';
            } elseif ($field->type == 'timestamp') {
                $fields .= '$table->timestamp("';
            } elseif ($field->type == 'relationship') {
                $fields .= '$table->unsignedBigInteger("';
            }

            if ($field->type == 'decimal') {
                // Add field name
                $fields .= $field->name . '", 13, 2)';
            } else {
                // Add field name
                $fields .= $field->name . '")';
            }

            // Check if field should be nullable
            if ($field->nullable) {
                $fields .= '->nullable()';
            }

            // Check if field has a default value
            if (!is_null($field->default)) {
                $fields .= '->default("' . $field->default . '")';
            }

            if ($field->relationship_field) {
                $fields .= ';
            $table->foreign("' . $field->name . '")->references("' . $field->relationship_field . '")->on("' . $field->relationship_table . '")';

                if ($field->relationship_cascade) {
                    $fields .= '->onDelete("cascade")->onUpdate("cascade")';
                }
            }

            // Add semicolon
            $fields .= ';
            ';
        }

        // Get migration stub
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/migration.stub');

        // Replace placeholders
        $new_migration = str_replace('{{BREAD}}', $this->argument('name'), $migration);
        $new_migration = str_replace('{{SLUG_PLURAL}}', $this->slug_plural, $new_migration);
        $new_migration = str_replace('{{ADDITIONAL_ROWS}}', $fields, $new_migration);

        // Get new migration filename
        $filename = base_path('database/migrations') . '/' . now()->format('Y_m_d_His') . '_create_' . $this->slug_plural . '_table.php';

        // Create migration
        file_put_contents($filename, $new_migration);
    }

    /**
     * Delete migration file
     *
     * @return void
     */
    private function deleteMigration()
    {
        // Delete the last migration
        $previous_migration = collect(File::files(base_path('database/migrations')))->last();

        // Delete previous migration
        File::delete($previous_migration);
    }

    /**
     * Generate seeder file
     *
     * @return void
     */
    private function generateSeeder()
    {
        // Give feedback
        $this->info('Creating seeder.');

        $this->artisan::call('make:seeder', [
            'name' => $this->argument('name') . 'Seeder'
        ]);
    }

    /**
     * Update seeder file
     *
     * @return void
     */
    private function updateSeeder()
    {
        // Give feedback
        $this->info('Updating seeder.');

        // Delete seeder
        $this->deleteSeeder();

        // New rows
        $rows = "";

        // Assemble new migration rows
        foreach ($this->rows as $row) {
            $row_count = $row->key;

            if ($row->type == 'text') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "text",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'rich_textbox') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "rich_text_box",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'number') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "number",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'boolean') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "checkbox",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'select') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "select_dropdown",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'date') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "date",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'datetime') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "datetime",
                "display_name" => __("' . $row->name . '"),
                "details"      => "",';
            } elseif ($row->type == 'relationship') {
                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "text",
                "display_name" => __("' . $row->name . '"),
                "required"     => 1,
                "browse"       => 1,
                "read"         => 1,
                "edit"         => 1,
                "add"          => 1,
                "delete"       => 1,
                "details"      => "",
                "order"        => ' . $row_count . ',
            ])->save();
        }';

                $rows .= '
        $dataRow = $this->dataRow($resourceDataTypes, "' . $this->fields[$row->field]->name  . '_relation");
        if (!$dataRow->exists) {
            $dataRow->fill([
                "type"         => "relationship",
                "display_name" => __("' . $row->name . '"),
                "details"      => \'{"model":"App\\\\' . $this->fields[$row->field]->relationship_model . '","table":"' . $this->fields[$row->field]->relationship_table  . '","type":"belongsTo","column":"' . $this->fields[$row->field]->name  . '","key":"' . $this->fields[$row->field]->relationship_field  . '","label":"' . $this->fields[$row->field]->relationship_label . '","pivot_table":"' . $this->slug_plural . '","pivot":"0"}\',';
            }

            // Get permissions
            if (in_array('required', $row->permissions)) {
                $rows .= '
                "required"     => 1,';
            } else {
                $rows .= '
                "required"     => 0,';
            }
            if (in_array('browse', $row->permissions)) {
                $rows .= '
                "browse"     => 1,';
            } else {
                $rows .= '
                "browse"     => 0,';
            }
            if (in_array('read', $row->permissions)) {
                $rows .= '
                "read"     => 1,';
            } else {
                $rows .= '
                "read"     => 0,';
            }
            if (in_array('edit', $row->permissions)) {
                $rows .= '
                "edit"     => 1,';
            } else {
                $rows .= '
                "edit"     => 0,';
            }
            if (in_array('add', $row->permissions)) {
                $rows .= '
                "add"     => 1,';
            } else {
                $rows .= '
                "add"     => 0,';
            }
            if (in_array('delete', $row->permissions)) {
                $rows .= '
                "delete"     => 1,';
            } else {
                $rows .= '
                "delete"     => 0,';
            }

            $rows .= '
                "order"        => ' . $row_count . ',
                        ])->save();
                    }';
        }

        // Get seeder_trait trait stub
        $seeder_trait = file_get_contents(__DIR__ . '/../../app/Traits/Bread/Bread.stub');

        // Replace placeholders
        $new_seeder_trait = str_replace('{{BREAD}}', $this->argument('name'), $seeder_trait);
        $new_seeder_trait = str_replace('{{SLUG}}', $this->slug, $new_seeder_trait);
        $new_seeder_trait = str_replace('{{SINGULAR}}', $this->option('singular'), $new_seeder_trait);
        $new_seeder_trait = str_replace('{{PLURAL}}', $this->option('plural'), $new_seeder_trait);
        $new_seeder_trait = str_replace('{{ICON}}', $this->option('icon'), $new_seeder_trait);
        $new_seeder_trait = str_replace('{{ORDER}}', $this->option('order'), $new_seeder_trait);
        $new_seeder_trait = str_replace('{{SORT}}', $this->option('sort'), $new_seeder_trait);
        $new_seeder_trait = str_replace('{{ADDITIONAL_ROWS}}', $rows, $new_seeder_trait);

        // Get new seeder_trait filename
        $filename = app_path('Traits/') . $this->argument('name') . '/' . $this->argument('name') . '.php';

        // Create directory if doesn't exist
        if (!is_dir(app_path('Traits/') . $this->argument('name'))) {
            mkdir(app_path('Traits/') . $this->argument('name'), 0777, true);
        }

        // Create seeder_trait
        file_put_contents($filename, $new_seeder_trait);

        // Get seeder trait stub
        $seeder = file_get_contents(__DIR__ . '/../../database/seeds/seeder.stub');

        // Replace placeholders
        $new_seeder = str_replace('{{BREAD}}', $this->argument('name'), $seeder);
        $new_seeder = str_replace('{{SLUG}}', $this->slug, $new_seeder);

        // Get new seeder filename
        $filename = base_path('database/seeds/' . $this->argument('name') . '/' . $this->argument('name') . 'Seeder.php');

        // Create directory if doesn't exist
        if (!is_dir(base_path('database/seeds/' . $this->argument('name')))) {
            mkdir(base_path('database/seeds/' . $this->argument('name')), 0777, true);
        }

        // Create seeder
        file_put_contents($filename, $new_seeder);
    }

    /**
     * Delete seeder file
     *
     * @return void
     */
    private function deleteSeeder()
    {
        // Delete the last seeder
        $previous_seeder = base_path('database/seeds/' . $this->argument('name') . 'Seeder.php');

        // Delete previous seeder
        File::delete($previous_seeder);
    }

    /**
     * Update seeder file
     *
     * @return void
     */
    private function updateDatabaseSeeder()
    {
        // Get file name
        $file = base_path('database/seeds/DatabaseSeeder.php');
        // Get file lines
        $file_lines = file($file, FILE_IGNORE_NEW_LINES);

        // Handle fun function line
        $run_line = 0;

        // Check if call have been inserted
        $inserted = false;

        // Loop through each line
        foreach ($file_lines as $key => $line) {
            // Get run position line
            if (strpos($line, 'public function run') != false) {
                $run_line = $key;
            }

            // Insert new call
            if ($run_line > 0 && strpos($file_lines[$run_line + 1], '{') != false && !$inserted) {
                $file_lines[$run_line + 2] = '        $this->call(' . $this->argument('name') . 'Seeder::class);
' . $file_lines[$run_line + 2];
                $inserted = true;
            } elseif ($run_line > 0 && strpos($file_lines[$run_line + 1], '{') == false && !$inserted) {
                $file_lines[$run_line + 1] = '        $this->call(' . $this->argument('name') . 'Seeder::class);
' . $file_lines[$run_line + 1];
                $inserted = true;
            }
        }

        // Insert new file contents
        file_put_contents($file, implode(PHP_EOL, $file_lines), LOCK_EX);
    }

    /**
     * Generate request files
     *
     * @return void
     */
    private function generateRequests()
    {
        // Get the file names
        $store_filename = app_path('Http/Requests/' . $this->argument('name') . '/StoreRequest.php');
        $update_filename = app_path('Http/Requests/' . $this->argument('name') . '/UpdateRequest.php');

        // Process if controller doesn't exist
        if (!file_exists($store_filename) || !file_exists($update_filename)) {
            // Give feedback
            $this->info('Creating requests.');

            // New fields
            $fields = "";

            // Get prepare for validation if needed
            $prepare_function = '';

            // Create directory if doesn't exist
            if (!is_dir(app_path('Http/Requests/' . $this->argument('name')))) {
                mkdir(app_path('Http/Requests/' . $this->argument('name')), 0777, true);
            }

            // Assemble new migration fields
            foreach ($this->fields as $field) {
                // Add field name
                $fields .= '
                "' . $field->name . '" => ["required"';

                if ($field->type == 'varchar') {
                    $fields .= ', "string"],';
                } elseif ($field->type == 'tiny_integer') {
                    $fields .= ', "numeric", "min:0"],';
                } elseif ($field->type == 'integer') {
                    $fields .= ', "numeric", "min:0"],';
                } elseif ($field->type == 'decimal') {
                    $fields .= ', "numeric", "min:0"],';
                } elseif ($field->type == 'boolean') {
                    $fields .= '],';

                    $prepare_function .= '
                    $this->merge([
                        "' . $field->name . '" => $this->' . $field->name . ' == "on",
                    ]);';
                } elseif ($field->type == 'timestamp') {
                    $fields .= '],';
                } elseif ($field->type == 'relationship') {
                    $fields .= '],';
                }
            }

            // Create store request
            if (!file_exists($store_filename)) {
                // Get store_request stub
                $store_request = file_get_contents(__DIR__ . '/../../app/Http/Requests/Bread/StoreRequest.stub');

                // Replace placeholders
                $new_store_request = str_replace('{{BREAD}}', $this->argument('name'), $store_request);
                $new_store_request = str_replace('{{ADDITIONAL_CONTENT}}', $fields, $new_store_request);
                $new_store_request = str_replace('{{PREPARE}}', $prepare_function, $new_store_request);

                // Get new store_request filename
                $filename = app_path('Http/Requests/' . $this->argument('name') . '/StoreRequest.php');

                // Replace placeholders

                // Create store_request
                file_put_contents($filename, $new_store_request);
            }

            // Create update request
            if (!file_exists($update_filename)) {
                // Get update_request stub
                $update_request = file_get_contents(__DIR__ . '/../../app/Http/Requests/Bread/UpdateRequest.stub');

                // Replace placeholders
                $new_update_request = str_replace('{{BREAD}}', $this->argument('name'), $update_request);
                $new_update_request = str_replace('{{ADDITIONAL_CONTENT}}', $fields, $new_update_request);
                $new_update_request = str_replace('{{PREPARE}}', $prepare_function, $new_update_request);

                // Get new update_request filename
                $filename = app_path('Http/Requests/' . $this->argument('name') . '/UpdateRequest.php');

                // Create update_request
                file_put_contents($filename, $new_update_request);
            }
        }
    }
}
