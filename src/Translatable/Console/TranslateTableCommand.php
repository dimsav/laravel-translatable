<?php

namespace Dimsav\Translatable\Console;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Console\Migrations\BaseCommand;

class TranslateTableCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:translate {name : The name of the translate table.}
        {--path= : The location where the migration file should be created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new translate migration file';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Dimsav\Translatable\Console\MigrationCreator $creator
     * @param  \Illuminate\Support\Composer $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // create the translate model
        $this->call('make:model', [
            'name' => $this->getModelName(trim($this->input->getArgument('name'))),
        ]);

        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = $this->getMigrationName(trim($this->input->getArgument('name')));

        $table = $this->getTableName(trim($this->input->getArgument('name')));
        $create = true;

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create);

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the translate table name.
     *
     * @param string $table
     * @return string
     */
    protected function getTableName($table)
    {
        $translation_suffix = Str::snake(Str::plural(App::make('config')->get('translatable.translation_suffix', 'Translation')));

        return Str::snake(Str::singular(class_basename($table)).'_'.$translation_suffix);
    }

    /**
     * Get the translate model class name.
     *
     * @param $table
     * @return string
     */
    protected function getModelName($table)
    {
        $config = App::make('config');

        $suffix = $config->get('translatable.translation_suffix', 'Translation');

        if ($config->get('translatable.translation_models_namespace')) {
            $namespace = $config->get('translatable.translation_models_namespace').'\\';
        } else {
            $namespace = '';
        }

        return $namespace.Str::studly(Str::singular(class_basename($table))).$suffix;
    }

    /**
     * Get the translate migration file name.
     *
     * @param string $name
     * @return string
     */
    protected function getMigrationName($name)
    {
        return 'create_'.$this->getTableName($name).'_table';
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string $name
     * @param  string $table
     * @param  bool $create
     * @return string
     */
    protected function writeMigration($name, $table, $create)
    {
        $file = pathinfo($this->creator->create($name, $this->getMigrationPath(), $table, $create), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> {$file}");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return $this->laravel->basePath().'/'.$targetPath;
        }

        return parent::getMigrationPath();
    }
}
