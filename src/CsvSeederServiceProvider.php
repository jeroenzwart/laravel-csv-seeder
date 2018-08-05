<?php

namespace JeroenZwart\CsvSeeder;

use Illuminate\Support\ServiceProvider;

class CsvSeederServiceProvider extends ServiceProvider
{
    /**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
    protected $defer = false;
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //$this->package('Jeroenzwart/csv-seeder');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
