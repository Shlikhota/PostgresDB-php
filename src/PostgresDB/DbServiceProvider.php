<?php namespace PostgresDB;

use Illuminate\Support\ServiceProvider;

class DbServiceProvider extends ServiceProvider {

    public function boot() {}

    public function register()
    {
        $this->app->bind('db', function($app) {
            $db = new Driver($app['config']['database.connections.pgsql'], 'Log');
            $debug = (env('APP_ENV') === 'development');
            if ($debug === true) {
                $db->setDebug(true)->isLog(true);
            }
            return $db;
        });
    }

}
