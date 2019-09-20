<?php

namespace Oyhdd\StatsCenter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class StatsCenterServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $commands = [
        Console\StatsServer::class,
        Console\StatsSum::class,
        Console\Install::class,
    ];

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'Oyhdd\StatsCenter\Http\Controllers';

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        //
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 自动注册
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('StatsCenter', \Oyhdd\StatsCenter\Facades\StatsCenter::class);

        // Publish configuration files
        $this->publishes([
            __DIR__.'/../config/statscenter.php' => config_path('statscenter.php')
        ]);

        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->map();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/statscenter.php', 'statscenter'
        );

        $this->mergeDefaultConfig(__DIR__.'/../config/admin.php', 'admin');

        // 单例模式
        $this->app->singleton('statscenter', function($app)
        {
            return $app->make(StatsCenter::class);
        });

        $this->commands($this->commands);
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::namespace($this->namespace)
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * 将package中的配置覆盖项目的配置
     */
    protected function mergeDefaultConfig($path, $key)
    {
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge($config, require $path));
    }
}
