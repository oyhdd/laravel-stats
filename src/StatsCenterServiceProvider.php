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
        Console\StatsClear::class,
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

        $this->loadViewsFrom(__DIR__ . '/Views', 'stats');// 指定视图目录

        // Publish configuration files
        $this->publishes([
            // __DIR__.'/Views' => base_path('resources/views/stats'),// 发布视图目录到resources 下
            dirname(__DIR__).'/config/statscenter.php' => config_path('statscenter.php'),
            dirname(__DIR__).'/assets' => public_path('vendor/stats'),// 发布资源文件到public下
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
        $disks_admin = $this->app['config']->get('filesystems.disks.admin', []);
        $config = $this->app['config']->get('filesystems', []);
        if (empty($config['disks']['admin'])) {
            $config['disks'] = array_merge($config['disks'], require __DIR__.'/../config/filesystems.php');
            $this->app['config']->set('filesystems', $config);
        }

        // 单例模式
        $this->app->singleton('statscenter', function($app) {
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
     * 将当前配置覆盖项目的配置
     */
    protected function mergeDefaultConfig($path, $key)
    {
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge($config, require $path));
    }
}
