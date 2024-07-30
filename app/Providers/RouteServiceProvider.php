<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';
    protected $namespace = 'App\Http\Controllers';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->MapApiRoutes();
        $this->MapWebRoutes();
        $this->MapClientApiRoutes();
        $this->MapUserApiRoutes();
        $this->MapAdminApiRoutes();
        $this->MapExternalApiRoutes();
    }

    protected function MapApiRoutes()
    {
        Route::prefix('api')
            // ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function MapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    protected function MapClientApiRoutes()
    {
        Route::prefix('api')
            ->middleware('clientapi')
            ->namespace($this->namespace)
            ->group(base_path('routes/app/client.php'));
    }

    protected function MapUserApiRoutes()
    {
        Route::prefix('api')
            ->middleware('userapi')
            ->namespace($this->namespace)
            ->group(base_path('routes/app/user.php'));
    }

    protected function MapAdminApiRoutes()
    {
        Route::prefix('api')
            ->middleware('adminapi')
            ->namespace($this->namespace)
            ->group(base_path('routes/app/admin.php'));
    }

    protected function MapExternalApiRoutes()
    {
        Route::prefix('api')
            ->middleware('externalapi')
            ->namespace($this->namespace)
            ->group(base_path('routes/app/external.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('clientapi', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('userapi', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('adminapi', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('externalapi', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
