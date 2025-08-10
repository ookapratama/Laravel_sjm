<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    // app/Providers/AppServiceProvider.php


public function boot(): void
{
    View::composer('*', function ($view) {
        if (auth()->check()) {
            $notifications = Notification::where('user_id', auth()->id())
            ->where('is_read', 0)
                ->latest()
                ->take(10)
                ->get();
        } else {
            $notifications = collect(); // jika belum login, kirim collection kosong
        }
        
        $view->with('notifications', $notifications);
    });
}

}
