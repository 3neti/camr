<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Location;
use App\Models\Meter;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global search across all entities
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->input('q');
        $results = [];

        // Search Sites
        $sites = Site::where('code', 'LIKE', "%{$query}%")
            ->orWhereHas('company', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($sites as $site) {
            $results[] = [
                'type' => 'site',
                'id' => $site->id,
                'name' => $site->code,
                'subtitle' => $site->company->name ?? null,
                'url' => route('sites.show', $site),
            ];
        }

        // Search Gateways
        $gateways = Gateway::where('serial_number', 'LIKE', "%{$query}%")
            ->orWhere('mac_address', 'LIKE', "%{$query}%")
            ->orWhere('ip_address', 'LIKE', "%{$query}%")
            ->with('site')
            ->limit(5)
            ->get();

        foreach ($gateways as $gateway) {
            $results[] = [
                'type' => 'gateway',
                'id' => $gateway->id,
                'name' => $gateway->serial_number,
                'subtitle' => "Site: {$gateway->site->code}",
                'url' => route('gateways.show', $gateway),
            ];
        }

        // Search Meters
        $meters = Meter::where('name', 'LIKE', "%{$query}%")
            ->orWhere('customer_name', 'LIKE', "%{$query}%")
            ->orWhere('type', 'LIKE', "%{$query}%")
            ->with(['gateway.site'])
            ->limit(5)
            ->get();

        foreach ($meters as $meter) {
            $results[] = [
                'type' => 'meter',
                'id' => $meter->id,
                'name' => $meter->name,
                'subtitle' => "Site: {$meter->gateway->site->code} • Gateway: {$meter->gateway->serial_number}",
                'url' => route('meters.show', $meter),
            ];
        }

        // Search Users
        $users = User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($users as $user) {
            $results[] = [
                'type' => 'user',
                'id' => $user->id,
                'name' => $user->name,
                'subtitle' => $user->email,
                'url' => route('users.show', $user),
            ];
        }

        // Search Locations
        $locations = Location::where('code', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->with(['site', 'building'])
            ->limit(5)
            ->get();

        foreach ($locations as $location) {
            $buildingInfo = $location->building ? " • Building: {$location->building->code}" : '';
            $results[] = [
                'type' => 'location',
                'id' => $location->id,
                'name' => $location->code,
                'subtitle' => "Site: {$location->site->code}{$buildingInfo}",
                'url' => route('locations.show', $location),
            ];
        }

        // Search Configuration Files
        $configFiles = ConfigurationFile::where('meter_model', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($configFiles as $config) {
            $results[] = [
                'type' => 'config_file',
                'id' => $config->id,
                'name' => $config->meter_model,
                'subtitle' => "{$config->meters_count} meters using this config",
                'url' => route('config-files.show', $config),
            ];
        }

        // Sort results by relevance (exact matches first)
        usort($results, function ($a, $b) use ($query) {
            $aExact = stripos($a['name'], $query) === 0 ? 0 : 1;
            $bExact = stripos($b['name'], $query) === 0 ? 0 : 1;
            return $aExact - $bExact;
        });

        return response()->json([
            'query' => $query,
            'results' => array_slice($results, 0, 20), // Limit to 20 total results
        ]);
    }
}
