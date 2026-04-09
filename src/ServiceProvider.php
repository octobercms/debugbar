<?php declare(strict_types=1);

namespace October\Debugbar;

use App;
use Event;
use Config;
use Cms\Classes\Page;
use Cms\Classes\Layout;
use Cms\Classes\Controller as CmsController;
use Backend\Classes\Controller as BackendController;
use Fruitcake\LaravelDebugbar\ServiceProvider as BaseServiceProvider;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use October\Debugbar\DataCollectors\OctoberBackendCollector;
use October\Debugbar\DataCollectors\OctoberCmsCollector;
use October\Debugbar\DataCollectors\OctoberComponentsCollector;
use October\Debugbar\DataCollectors\OctoberModelsCollector;
use October\Debugbar\Middleware\InterpretsAjaxExceptions;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

/**
 * ServiceProvider for October CMS Debugbar integration
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * register the service provider
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * boot the service provider
     */
    public function boot(): void
    {
        parent::boot();

        if (!LaravelDebugbar::canBeEnabled()) {
            return;
        }

        $debugbar = $this->app->make(LaravelDebugbar::class);

        if (!$debugbar->isEnabled()) {
            return;
        }

        // Register AJAX exception middleware
        if (Config::get('app.debug_ajax', Config::get('app.debugAjax', false))) {
            $this->app[HttpKernelContract::class]->pushMiddleware(InterpretsAjaxExceptions::class);
        }

        // Custom debugbar collectors and extensions
        $this->registerResourceInjection($debugbar);

        if (App::runningInBackend()) {
            $this->addBackendCollectors($debugbar);
        }
        else {
            $this->registerCmsTwigExtensions($debugbar);
            $this->addFrontendCollectors($debugbar);
        }

        $this->addGlobalCollectors($debugbar);
    }

    /**
     * addGlobalCollectors adds globally available collectors
     */
    protected function addGlobalCollectors(LaravelDebugbar $debugbar): void
    {
        $modelsCollector = $this->app->make(OctoberModelsCollector::class);
        $debugbar->addCollector($modelsCollector);
    }

    /**
     * addFrontendCollectors used by the frontend only
     */
    protected function addFrontendCollectors(LaravelDebugbar $debugbar): void
    {
        Event::listen('cms.page.beforeDisplay', function (CmsController $controller, $url, ?Page $page) use ($debugbar) {
            if ($page) {
                $collector = new OctoberCmsCollector($controller, $url, $page);
                if (!$debugbar->hasCollector($collector->getName())) {
                    $debugbar->addCollector($collector);
                }
            }
        });

        Event::listen('cms.page.initComponents', function (CmsController $controller, ?Page $page, ?Layout $layout) use ($debugbar) {
            if ($page) {
                $collector = new OctoberComponentsCollector($controller, $page, $layout);
                if (!$debugbar->hasCollector($collector->getName())) {
                    $debugbar->addCollector($collector);
                }
            }
        });
    }

    /**
     * addBackendCollectors used by the backend only
     */
    protected function addBackendCollectors(LaravelDebugbar $debugbar): void
    {
        Event::listen('backend.page.beforeDisplay', function (BackendController $controller, $action, array $params) use ($debugbar) {
            $collector = new OctoberBackendCollector($controller, $action, $params);
            if (!$debugbar->hasCollector($collector->getName())) {
                $debugbar->addCollector($collector);
            }
        });
    }

    /**
     * registerCmsTwigExtensions in the CMS Twig environment
     */
    protected function registerCmsTwigExtensions(LaravelDebugbar $debugbar): void
    {
        $profile = new Profile;

        Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) use ($profile, $debugbar) {
            $twig = $controller->getTwig();

            if (!$twig->hasExtension(\Fruitcake\LaravelDebugbar\Twig\Extension\Debug::class)) {
                $twig->addExtension(new \Fruitcake\LaravelDebugbar\Twig\Extension\Debug($this->app));
                $twig->addExtension(new \Fruitcake\LaravelDebugbar\Twig\Extension\Stopwatch($this->app));
            }

            if (!$twig->hasExtension(ProfilerExtension::class)) {
                $twig->addExtension(new ProfilerExtension($profile));
            }
        });

        if (class_exists(\DebugBar\Bridge\NamespacedTwigProfileCollector::class)) {
            $debugbar->addCollector(new \DebugBar\Bridge\NamespacedTwigProfileCollector($profile));
        }
        elseif (class_exists(\DebugBar\Bridge\TwigProfileCollector::class)) {
            $debugbar->addCollector(new \DebugBar\Bridge\TwigProfileCollector($profile));
        }
    }

    /**
     * registerResourceInjection adds October branding to the debugbar
     */
    protected function registerResourceInjection(LaravelDebugbar $debugbar): void
    {
        $css = file_get_contents(__DIR__.'/../assets/css/debugbar.css');

        $debugbar->getJavascriptRenderer()->addInlineAssets(
            ['october-debugbar' => $css],
            [],
            []
        );
    }
}