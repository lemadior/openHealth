<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\LegalEntity;
use App\Models\User;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        if (!$this->app->runningInConsole()) {
            $this->app->singletonIf(LegalEntity::class, function($app) {
                /** @var Request $request */
                $request = $app->make(Request::class);

                $legalEntity = null;

                /* Highest priority for retrieve a LegalEntity (setted by ResolveLegalEntity middlewaere) */
                if ($request->attributes->has('current_legal_entity')) {
                    $legalEntity = $request->attributes->get('current_legal_entity');
                }

                /*
                 * Get LegalEntity from the authenticated User object (setted by 'ehealth' guard).
                 * It works if 'current_legal_entity' not setted by middleware or if it the first request ever
                 * after login before middleware will start
                 */
                if (! $legalEntity && Auth::check() && Auth::user() instanceof User) {
                    $legalEntity = Auth::user()->legalEntity;
                }

                return $legalEntity;
            });

            $this->app->alias(LegalEntity::class, 'legalEntity');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Model::shouldBeStrict($this->app->isLocal());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
