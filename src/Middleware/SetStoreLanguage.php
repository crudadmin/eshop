<?php

namespace AdminEshop\Middleware;

use Admin\Eloquent\AdminModel;
use Closure;
use Illuminate\Http\Request;
use Localization;

class SetStoreLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        AdminModel::$localizedResponseArray = false;

        //Update language
        if ( $langCode = request()->header('app-locale') ){
            Localization::setLocale($langCode);
        }

        return $next($request);
    }
}
