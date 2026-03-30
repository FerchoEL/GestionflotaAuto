<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AlertaRendimiento;
use App\Models\AlertaFondeo;
use App\Observers\AlertaRendimientoObserver;
use App\Observers\AlertaFondeoObserver;

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
        config()->set('livewire.temporary_file_upload.disk', 'public');
        config()->set('livewire.temporary_file_upload.directory', 'livewire-tmp');
        config()->set('livewire.temporary_file_upload.rules', [
            'required',
            'file',
            'max:20480',
        ]);
        config()->set('livewire.temporary_file_upload.preview_mimes', [
            'png',
            'gif',
            'bmp',
            'svg',
            'jpg',
            'jpeg',
            'webp',
            'avif',
            'heic',
            'heif',
        ]);

        AlertaRendimiento::observe(AlertaRendimientoObserver::class);
        AlertaFondeo::observe(AlertaFondeoObserver::class);
    }
}
