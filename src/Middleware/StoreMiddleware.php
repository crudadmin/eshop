<?php

namespace AdminEshop\Middleware;

use Closure;

class StoreMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $type = null)
    {
        $tokenName = config('admineshop.cart.token.header_name');

        if ( config('admineshop.cart.session') === false && empty($request->header($tokenName)) ){
            return abort(401, $tokenName.' header has not been set.');
        }

        return $next($request);
    }
}
