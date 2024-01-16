<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $access = null)
    {
        $staff =  Auth::guard('admin')->user();
        $staffAccess = $staff->staff_access;
        if(in_array($access, $staffAccess))
        {
            return $next($request);
        }
        abort(403);
    }
}
