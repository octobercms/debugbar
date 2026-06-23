<p align="center"><img src="https://octobercms.com/themes/website/assets/images/october-color-logo.svg" width="200" alt="October CMS"></p>

## Introduction

October CMS Debugbar integrates [Laravel Debugbar](https://github.com/fruitcake/laravel-debugbar) with October CMS, adding custom data collectors for backend controllers, CMS pages, components, and models.

## Requirements

- October CMS 4.x
- PHP 8.2+

## Installation

```bash
composer require october/debugbar --dev
```

The debugbar is enabled automatically when `APP_DEBUG=true`. You can override this with the `DEBUGBAR_ENABLED` environment variable.

## Access Control

The toolbar is only injected for signed-in backend super users. Anonymous frontend visitors and non-super-user backend admins see no debugbar, and no debugbar data is written to disk for their requests. This makes it safe to leave `DEBUGBAR_ENABLED=true` on a live site when you need to debug a production-only issue, since only super users can see the captured data.

## October CMS Collectors

In addition to the standard Laravel Debugbar collectors, the following October-specific collectors are included:

Collector | Description
--- | ---
**Backend** | Shows the backend controller, action, parameters, and AJAX handler with file location
**CMS** | Shows the CMS page, URL, AJAX handler, and page properties with file location
**Components** | Lists all components from the page and layout with their class and properties
**Models** | Tracks October model instantiation counts via the `model.afterFetch` event

## Configuration

Publish the configuration file to customize collector settings:

```bash
php artisan vendor:publish --provider="October\Debugbar\ServiceProvider" --tag=config
```

Or create `config/debugbar.php` manually. See the [default configuration](config/debugbar.php) for available options.

## AJAX Debugging

AJAX requests are captured by the debugbar automatically and displayed in the toolbar dropdown. To disable this, set `capture_ajax` to `false` in `config/debugbar.php`.

## Troubleshooting

### Out of memory errors

If the debugbar causes out-of-memory errors, the `cache` collector is the most common cause on requests that perform many cache reads. It is disabled by default; if you have enabled it, set it back to `false` in `config/debugbar.php`:

```php
'collectors' => [
    'cache' => env('DEBUGBAR_COLLECTORS_CACHE', false), // Display cache events
],
```

The `models`, `views`, and `events` collectors can also significantly increase memory usage and are disabled by default for the same reason.

## License

October CMS Debugbar is open-sourced software licensed under the [MIT license](LICENSE.md).
