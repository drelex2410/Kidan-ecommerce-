<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsSeller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $shop = Auth::check() ? Auth::user()->shop : null;

        if (Auth::check() && Auth::user()->user_type == 'seller' && $shop && $shop->approval == 1) {
            return $next($request);
        }
        else{
            abort(404);
        }
    }
}
