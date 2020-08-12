<?php

namespace AdminEshop\Middleware;

use Closure;
use Cart;

class CartMiddleware
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
        //If no items has been added
        if ( Cart::all()->count() == 0 ){
            return redirect('/');
        }

        return $next($request);
    }
}
