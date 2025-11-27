<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Support\Facades\Log;

class GatewayPollingController extends Controller
{
    /**
     * Check if gateway needs CSV meter list update
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function checkCsvUpdate(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('0');
            }

            $flag = $gateway->update_csv ? '1' : '0';

            Log::info('Gateway CSV update check', [
                'mac' => $mac,
                'update_csv' => $flag,
            ]);

            return $this->plainTextResponse($flag);
        } catch (\Exception $e) {
            Log::error('Failed to check CSV update', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('0');
        }
    }

    /**
     * Get CSV content of meter list for gateway
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function getCsvContent(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                Log::warning('CSV content requested for unknown gateway', ['mac' => $mac]);

                return $this->csvResponse('');
            }

            // Get first 32 active meters for this gateway with their config files
            $meters = $gateway->meters()
                ->where('status', 'Active')
                ->with('configurationFile')
                ->limit(32)
                ->get();

            $csvLines = [];

            foreach ($meters as $meter) {
                // Get config file name (default to empty if not set)
                $configFile = $meter->configurationFile?->meter_model ?? '';

                // Apply legacy name logic
                if ($meter->name == $meter->default_name) {
                    $meterName = $meter->name;
                    $addressable = $meter->default_name;
                } elseif ($meter->default_name == '1') {
                    $meterName = '1';
                    $addressable = $meter->default_name;
                } else {
                    $meterName = $meter->name;
                    $addressable = $meter->default_name;
                }

                // Build CSV line: meter_name,config_file,addressable_meter
                $csvLines[] = sprintf('%s,%s,%s', $meterName, $configFile, $addressable);
            }

            $csvContent = implode("\n", $csvLines);

            Log::info('Gateway CSV content generated', [
                'mac' => $mac,
                'gateway_id' => $gateway->id,
                'meter_count' => count($csvLines),
            ]);

            return $this->csvResponse($csvContent);
        } catch (\Exception $e) {
            Log::error('Failed to generate CSV content', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->csvResponse('');
        }
    }

    /**
     * Reset CSV update flag after gateway downloads
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function resetCsvUpdate(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('OK');
            }

            $gateway->update(['update_csv' => false]);

            Log::info('Gateway CSV update flag reset', [
                'mac' => $mac,
                'gateway_id' => $gateway->id,
            ]);

            return $this->plainTextResponse('OK');
        } catch (\Exception $e) {
            Log::error('Failed to reset CSV update flag', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('OK');
        }
    }

    /**
     * Check if gateway needs site code update
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function checkSiteCodeUpdate(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('0');
            }

            $flag = $gateway->update_site_code ? '1' : '0';

            Log::info('Gateway site code update check', [
                'mac' => $mac,
                'update_site_code' => $flag,
            ]);

            return $this->plainTextResponse($flag);
        } catch (\Exception $e) {
            Log::error('Failed to check site code update', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('0');
        }
    }

    /**
     * Get site code for gateway
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function getSiteCode(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway || !$gateway->site_code) {
                Log::warning('Site code requested for unknown gateway or missing site code', ['mac' => $mac]);

                return $this->plainTextResponse('location = ""');
            }

            $response = sprintf('location = "%s"', $gateway->site_code);

            Log::info('Gateway site code retrieved', [
                'mac' => $mac,
                'gateway_id' => $gateway->id,
                'site_code' => $gateway->site_code,
            ]);

            return $this->plainTextResponse($response);
        } catch (\Exception $e) {
            Log::error('Failed to get site code', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('location = ""');
        }
    }

    /**
     * Reset site code update flag after gateway downloads
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function resetSiteCodeUpdate(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('OK');
            }

            $gateway->update(['update_site_code' => false]);

            Log::info('Gateway site code update flag reset', [
                'mac' => $mac,
                'gateway_id' => $gateway->id,
            ]);

            return $this->plainTextResponse('OK');
        } catch (\Exception $e) {
            Log::error('Failed to reset site code update flag', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('OK');
        }
    }

    /**
     * Check if gateway should force load profile upload
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function checkForceLoadProfile(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('0');
            }

            $flag = $gateway->force_load_profile ? '1' : '0';

            Log::info('Gateway force load profile check', [
                'mac' => $mac,
                'force_load_profile' => $flag,
            ]);

            return $this->plainTextResponse($flag);
        } catch (\Exception $e) {
            Log::error('Failed to check force load profile', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('0');
        }
    }

    /**
     * Reset force load profile flag after gateway uploads
     *
     * @param string $mac Gateway MAC address
     * @return \Illuminate\Http\Response
     */
    public function resetForceLoadProfile(string $mac)
    {
        try {
            $gateway = $this->findGatewayByMac($mac);

            if (!$gateway) {
                return $this->plainTextResponse('OK');
            }

            $gateway->update(['force_load_profile' => false]);

            Log::info('Gateway force load profile flag reset', [
                'mac' => $mac,
                'gateway_id' => $gateway->id,
            ]);

            return $this->plainTextResponse('OK');
        } catch (\Exception $e) {
            Log::error('Failed to reset force load profile flag', [
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return $this->plainTextResponse('OK');
        }
    }

    /**
     * Get current server time for gateway synchronization
     *
     * @return \Illuminate\Http\Response
     */
    public function getServerTime()
    {
        try {
            $timestamp = now()->format('Y-m-d H:i:s');

            Log::debug('Server time requested', ['timestamp' => $timestamp]);

            return $this->plainTextResponse($timestamp);
        } catch (\Exception $e) {
            Log::error('Failed to get server time', [
                'error' => $e->getMessage(),
            ]);

            // Fallback to raw PHP date
            return $this->plainTextResponse(date('Y-m-d H:i:s'));
        }
    }

    /**
     * Find gateway by MAC address
     *
     * @param string $mac
     * @return Gateway|null
     */
    private function findGatewayByMac(string $mac): ?Gateway
    {
        return Gateway::where('mac_address', $mac)->first();
    }

    /**
     * Return plain text response
     *
     * @param string $content
     * @return \Illuminate\Http\Response
     */
    private function plainTextResponse(string $content)
    {
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Return CSV response
     *
     * @param string $content
     * @return \Illuminate\Http\Response
     */
    private function csvResponse(string $content)
    {
        return response($content, 200)
            ->header('Content-Type', 'text/csv');
    }
}
