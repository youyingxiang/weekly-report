<?php

namespace Yxx\WeeklyReport;

use Yxx\WeeklyReport\Commands\WeeklyReportCommand;
use Yxx\WeeklyReport\Services\GitHubClient;
use Yxx\WeeklyReport\Services\GitLogParser;
use Yxx\WeeklyReport\Services\ReportGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WeeklyReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/weekly-report.php',
            'weekly-report'
        );

        $this->app->singleton(GitLogParser::class);

        $this->app->singleton(GitHubClient::class);

        $this->app->singleton(ReportGenerator::class, function ($app) {
            return new ReportGenerator(
                $app->make(GitLogParser::class),
                $app->make(GitHubClient::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/weekly-report.php' => config_path('weekly-report.php'),
        ], 'weekly-report-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'weekly-report');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/weekly-report'),
        ], 'weekly-report-views');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                WeeklyReportCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $prefix = 'weekly-report';

        Route::prefix($prefix)
            ->middleware('web')
            ->group(function () {
                Route::get('/confirm', [
                    Http\Controllers\ReportConfirmationController::class,
                    'confirm',
                ])->name('weekly-report.confirm');

                Route::get('/cancel', [
                    Http\Controllers\ReportConfirmationController::class,
                    'cancel',
                ])->name('weekly-report.cancel');
            });
    }
}
