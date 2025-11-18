<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageSiteContext
{
    /**
     * Handle an incoming request.
     *
     * Manages the selected site context in the session. This allows users to
     * select a site and have that context persist across different pages
     * (Buildings, Locations, Gateways, Meters, etc.)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user explicitly provides site_id in request, update session
        if ($request->has('site_id')) {
            $siteId = $request->input('site_id');
            
            // Clear context if site_id is 'clear', null, empty string, or 'all'
            if (in_array($siteId, ['clear', null, '', 'all'], true)) {
                $request->session()->forget('selected_site_id');
            } else {
                // Store the selected site ID in session (convert to int)
                $request->session()->put('selected_site_id', (int) $siteId);
            }
        }

        // If no site_id in request but we have one in session, merge it into request
        if (!$request->has('site_id') && $request->session()->has('selected_site_id')) {
            // Automatically apply the session site_id to the request
            // This makes it available to controllers without them needing to check session
            $request->merge(['site_id' => $request->session()->get('selected_site_id')]);
        }

        return $next($request);
    }
}
