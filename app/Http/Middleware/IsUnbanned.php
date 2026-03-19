<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsUnbanned
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->banned) {    
            if (method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            } elseif (method_exists($request->user(), 'token') && $request->user()->token()) {
                $request->user()->token()->revoke();
            }
            $message = translate("You are banned");
            flash($message);
            return response()->json([
                'success' => false,
                'message' => translate('You are banned!')
            ], 401);
        }
        return $next($request);
    }
}
