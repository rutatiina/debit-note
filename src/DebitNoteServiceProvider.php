<?php

namespace Rutatiina\DebitNote;

use Illuminate\Support\ServiceProvider;

class DebitNoteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes/routes.php';
        //include __DIR__.'/routes/api.php';

        $this->loadViewsFrom(__DIR__.'/resources/views', 'debit-note');
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Rutatiina\DebitNote\Http\Controllers\DebitNoteController');
    }
}
