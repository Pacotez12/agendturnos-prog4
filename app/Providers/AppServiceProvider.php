<?php

namespace App\Providers;

use App\Enums\RolUsuario;
use App\Services\AgendaService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // /health (health check de HAProxy): el nodo solo esta sano si llega a la base y a Redis
        Event::listen(DiagnosingHealth::class, function () {
            DB::select('select 1');
            Redis::ping();
        });

        View::composer('components.layouts.panel', function (ViewInstance $view) {
            $user = auth()->user();
            $solicitudesCount = 0;
            if ($user && in_array($user->rol, [RolUsuario::Profesional, RolUsuario::Funcionario], true)) {
                $solicitudesCount = app(AgendaService::class)->solicitudesPendientes($user)->count();
            }
            $view->with('solicitudesCount', $solicitudesCount);
        });

        RateLimiter::for('registro', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('recuperar-contrasena', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('solicitar-turno', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('familia', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            $limit = (int) config('agenda.rate_limit_api', 60);

            return $limit > 0
                ? Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip())
                : Limit::none();
        });

        RateLimiter::for('api-token', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by(strtolower(trim($email)).'|'.$request->ip());
        });

        // Todas las rutas con token Bearer; las públicas se marcan con @unauthenticated en el controlador.
        Scramble::afterOpenApiGenerated(fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer')));
    }
}
