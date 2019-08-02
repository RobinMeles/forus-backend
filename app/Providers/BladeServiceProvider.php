<?php

namespace App\Providers;

use App\Models\Implementation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $implementation = Implementation::activeKey();

        Blade::directive('implementationmail', function ($data) use ($implementation) {
            if (config()->has('forus.mails.implementations.' . $implementation . '.' . $data)) {
                return config('forus.mails.implementations.' . $implementation . '.' . $data);
            }
            elseif (config()->has('forus.mails.implementations.general.' . $data)) {
                return config('forus.mails.implementations.general.' . $data);
            }
            elseif(Lang::has('forus.mails.implementations.' . $implementation . '.' . $data)) {
                return Lang::get('forus.mails.implementations.' . $implementation . '.' . $data);
            }

            return Lang::get('forus.mails.implementations.general.' . $data);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
