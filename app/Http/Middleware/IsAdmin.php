<?php

namespace App\Http\Middleware;

use App\Http\Services\AdminShopService;
use Closure;
use Auth;

class IsAdmin
{
    protected $adminShopService;

    public function __construct(AdminShopService $adminShopService)
    {
        $this->adminShopService = $adminShopService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            if (!$this->adminShopService->ensureShopForUser(Auth::user())) {
                abort(500, 'Unable to resolve the inhouse shop for this account.');
            }

            return $next($request);
        }
        else{
            abort(404);
        }
    }
}
