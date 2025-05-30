<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to store URL's as intended urls when user wants to go into some page but it needs to redirect to authenticate before
 */
class StoreIntendedUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path(); // Get path without domain and without request parameters

        $isDashboardRoute = preg_match('/\/dashboard(\/.*)?$/', $path);

        /* Check for Livewire AJAX requests */
        $isLivewireHasXHR = class_exists(\Livewire\Livewire::class) && \Livewire\Livewire::isLivewireRequest();

        /* For Login Use special key to protect from rewriting by browser*/
        if (
            $isDashboardRoute &&
            !$request->expectsJson() &&
            !$isLivewireHasXHR &&
            $request->isMethod('get')
        ) {
            /* This wil use among other the password.confirm route(s) */
            session()->put('url.intended', $request->fullUrl());
        }

        return $next($request);
    }
}
