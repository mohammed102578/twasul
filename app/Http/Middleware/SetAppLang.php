<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetAppLang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next )
    {
        if(! in_array($request->segment(2),config('app.available_locales'))) {
            abort(400);
        }
        App::setLocale($request->segment(2));
        return $next($request);
       
    }
}
