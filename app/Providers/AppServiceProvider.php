<?php

namespace App\Providers;

use App\Models\FrontendSetting;
use App\Models\Menu;
use App\Services\SettingsRepository;
use App\Services\PortalNotificationService;
use App\Services\RecaptchaService;
use App\Services\OtpService;
use App\Services\SmtpConfigurator;
use App\Services\RevenueShareService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * App Service Provider
 *
 * This service provider registers the application services.
 *
 * @package App\Providers
 * @author Renatus Bernardo
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository();
        });

        $this->app->singleton(RecaptchaService::class, function () {
            return new RecaptchaService();
        });

        $this->app->singleton(OtpService::class, function () {
            return new OtpService();
        });

        $this->app->singleton(SmtpConfigurator::class, function ($app) {
            return new SmtpConfigurator($app->make(SettingsRepository::class));
        });

        $this->app->singleton(RevenueShareService::class, function ($app) {
            return new RevenueShareService($app->make(SettingsRepository::class));
        });

        $this->app->singleton(\App\Services\ThemeService::class, function () {
            return new \App\Services\ThemeService();
        });

        $this->app->singleton(PortalNotificationService::class, function () {
            return new PortalNotificationService();
        });

        $helpers = app_path('Support/helpers.php');

        if (file_exists($helpers)) {
            require_once $helpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDynamicAppUrl();

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(160)->by($request->user()?->id ?: $request->ip());
        });
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof \App\Models\User) {
                $this->app->make(PortalNotificationService::class)->handleLogin($event->user);
            }
        });

        if ($this->shouldDeferBootstrapping()) {
            return;
        }

        if (!$this->settingsTablesAreReady()) {
            return;
        }

        $this->app->make(SmtpConfigurator::class)->apply();

        View::share('platformSettings', platform_settings());
        View::share('brandingSettings', branding_settings());
        View::share('currencyCode', currency_code());
        View::share('currencySymbol', currency_symbol());

        Blade::directive('currency', function ($expression) {
            return "<?php echo currency_format($expression); ?>";
        });

        View::composer('partials.marquee', function ($view) {
            $items = collect(frontend_setting('pages.marquee.items', []))
                ->filter(fn($item) => filled($item['text'] ?? null))
                ->map(function ($item) {
                    $image = $item['image'] ?? '';

                    if (blank($image)) {
                        $imageUrl = asset('assets/front/img/home-1/icon1.png');
                    } elseif (Str::startsWith($image, ['http://', 'https://'])) {
                        $imageUrl = $image;
                    } else {
                        $imageUrl = asset(ltrim($image, '/'));
                    }

                    return [
                        'text' => $item['text'],
                        'image' => $imageUrl,
                    ];
                })
                ->values();

            $view->with([
                'marqueeItems' => $items,
                'marqueeChunks' => $items->chunk(7),
            ]);
        });

        View::composer([
            'partials.header',
            'partials.header-two',
            'partials.header-three',
            'partials.mobile-nav',
            'partials.footer',
            'home.*.partials.header',
            'home.*.partials.footer',
        ], function ($view) {
            $menuLocations = [
                'main-navigation',
                'mobile-navigation',
                'footer-links',
                'quick-links',
            ];

            $menus = Cache::remember('frontend.menus.locations', now()->addHour(), function () use ($menuLocations) {
                return Menu::query()
                    ->active()
                    ->whereIn('location', $menuLocations)
                    ->with([
                        'allItems' => function ($query) {
                            $query->active()->orderBy('order');
                        }
                    ])
                    ->get()
                    ->keyBy('location');
            });

            $menuTrees = [];
            foreach ($menuLocations as $location) {
                $tree = isset($menus[$location])
                    ? $this->buildMenuTree($menus[$location]->allItems)
                    : [];

                $menuTrees[$location] = $tree;
            }

            $view->with('frontendMenus', $menuTrees);

            $view->with('footerBranding', FrontendSetting::getValue('footer.branding', [
                'about_text' => null,
                'tagline' => null,
                'copyright' => null,
            ]));

            $view->with('footerContact', FrontendSetting::getValue('footer.contact', [
                'email' => null,
                'phone' => null,
                'address' => null,
                'hours' => null,
            ]));

            $view->with('footerSocialLinks', FrontendSetting::getValue('footer.social_links', []));
        });
    }

    /**
     * Dynamically bind app and public storage URLs to the incoming HTTP request host,
     * scheme, port, and base subdirectory for zero-config cross-server deployment.
     */
    private function configureDynamicAppUrl(): void
    {
        if ($this->app->runningInConsole() || !$this->app->bound('request')) {
            return;
        }

        $request = $this->app->make('request');
        $httpHost = $request->getHttpHost();

        if ($httpHost === '') {
            return;
        }

        $rootUrl = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        if ($rootUrl === '') {
            return;
        }

        URL::forceRootUrl($rootUrl);

        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        config([
            'app.url' => $rootUrl,
            'filesystems.disks.public.url' => $rootUrl . '/storage',
        ]);
    }

    /**
     * Should defer bootstrapping.
     */
    private function shouldDeferBootstrapping(): bool
    {
        if (!app()->runningInConsole()) {
            return false;
        }

        $arguments = collect($_SERVER['argv'] ?? [])
            ->implode(' ');

        return Str::contains($arguments, [
            'migrate',
            'db:seed',
            'db:wipe',
            'db:refresh',
            'db:fresh',
            'db:install',
        ]);
    }

    /**
     * Settings tables are ready.
     */
    private function settingsTablesAreReady(): bool
    {
        try {
            return Schema::hasTable('platform_settings')
                && Schema::hasTable('frontend_settings');
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Build menu tree.
     */
    protected function buildMenuTree(Collection $items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->sortBy('order')
            ->map(function ($item) use ($items) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->resolved_url,
                    'target' => $item->target,
                    'children' => $this->buildMenuTree($items, $item->id),
                ];
            })
            ->values()
            ->all();
    }

}
