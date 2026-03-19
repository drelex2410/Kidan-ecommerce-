<?php

namespace App\Providers;

use App\Contracts\AddonStatusManager;
use App\Contracts\ApplicationBootstrap;
use App\Contracts\ColorConverter as ColorConverterContract;
use App\Services\ApplicationBootstrapService;
use App\Support\Addons\AddonStatusService;
use App\Support\Colors\ColorConverter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    Paginator::useBootstrap();
    Schema::defaultStringLength(191);

    if (env('FORCE_HTTPS') === 'On') {
        URL::forceScheme('https');
    }
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(ApplicationBootstrap::class, ApplicationBootstrapService::class);
    $this->app->singleton(AddonStatusManager::class, AddonStatusService::class);
    $this->app->singleton(ColorConverterContract::class, ColorConverter::class);
  }
}
