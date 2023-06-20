<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

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
    public function boot()
    {
        Request::setTrustedProxies(['0.0.0.0/0'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);

    }
//    public function boot(UrlGenerator $url)
//    {
//        if (env('APP_ENV') == 'production') {
//            $url->forceScheme('https');
//        }
//    }
}
