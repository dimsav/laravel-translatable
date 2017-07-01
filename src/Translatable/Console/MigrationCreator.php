<?php

namespace Dimsav\Translatable\Console;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{
    /**
     * Get the migration stub file.
     *
     * @param  string $table
     * @param  bool $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        return $this->files->get($this->stubPath().'/translate.stub');
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string $name
     * @param  string $stub
     * @param  string $table
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);
        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (! is_null($table)) {
            $stub = str_replace('DummyTable', $table, $stub);
            $referencesTable = Str::plural(str_replace('_'.$this->getTranslationSuffix(), '', $table));
            $stub = str_replace('DummyReferencesTable', $referencesTable, $stub);
            $forignKey = Str::singular(str_replace('_'.$this->getTranslationSuffix(), '', $table)).'_id';
            $stub = str_replace('DummyForeign', $forignKey, $stub);
        }

        return $stub;
    }

    /**
     * Defines the default 'Translation' class suffix.
     *
     * @return string
     */
    protected function getTranslationSuffix()
    {
        return Str::snake(Str::plural(App::make('config')->get('translatable.translation_suffix', 'translation')));
    }

    /**
     * Get the class name of a migration name.
     *
     * @param  string $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
     *
     * @param  string $name
     * @param  string $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Fire the registered post create hooks.
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register a post migration create hook.
     *
     * @param  \Closure $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }
}