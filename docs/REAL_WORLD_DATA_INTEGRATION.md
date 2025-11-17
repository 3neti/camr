# Real-World Data Integration Guide

**Status:** 📋 Documentation - Ready for Implementation  
**Version:** 1.0  
**Last Updated:** 2025-11-17  

This document provides a complete roadmap for integrating the CAMR application with live meter reading equipment.

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Phase 1: Data Ingestion](#phase-1-data-ingestion)
4. [Phase 2: Real-Time Updates](#phase-2-real-time-updates)
5. [Phase 3: Data Visualization](#phase-3-data-visualization)
6. [Phase 4: Alert System](#phase-4-alert-system)
7. [Database Schema](#database-schema)
8. [API Specifications](#api-specifications)
9. [Security Considerations](#security-considerations)
10. [Testing Strategy](#testing-strategy)
11. [Performance Optimization](#performance-optimization)
12. [Troubleshooting](#troubleshooting)

---

## Overview

### Current State
The CAMR application has:
- ✅ Complete CRUD for Sites, Gateways, Meters
- ✅ Basic data visualization
- ✅ User management
- ❌ No live data ingestion
- ❌ No real-time updates
- ❌ No alerting system

### Target State
After implementation:
- ✅ Live meter reading ingestion
- ✅ Real-time dashboard updates
- ✅ Comprehensive alerting
- ✅ Advanced data visualization
- ✅ Data quality monitoring

### System Architecture

```
┌─────────────────┐
│ Physical Meters │ (Modbus/MQTT/API)
└────────┬────────┘
         │
┌────────▼────────┐
│    Gateways     │ (Data Collection)
└────────┬────────┘
         │
┌────────▼────────┐
│  Data Broker    │ (MQTT/Message Queue)
└────────┬────────┘
         │
┌────────▼────────┐
│ Laravel Backend │ (Processing & Storage)
├─────────────────┤
│ - Queue Workers │
│ - Alert Engine  │
│ - WebSocket     │
└────────┬────────┘
         │
┌────────▼────────┐
│  Vue Frontend   │ (Real-time Display)
└─────────────────┘
```

---

## Prerequisites

### Hardware Requirements

**Minimum:**
- **Meters:** Smart meters with communication capability
- **Gateways:** Data collection devices (Modbus RTU/TCP, MQTT, or REST API)
- **Network:** Stable connection between meters → gateways → server

**Recommended:**
- Industrial-grade smart meters (Itron, Landis+Gyr, Schneider Electric)
- Gateways with edge computing capability
- Redundant network paths

### Software Requirements

**Backend:**
```bash
# Core dependencies
composer require beyondcode/laravel-websockets  # Real-time updates
composer require predis/predis                   # Redis support
composer require laravel/horizon                 # Queue monitoring
composer require phpmqtt/client                 # MQTT (if needed)

# Optional for time-series
# TimescaleDB extension for PostgreSQL
# OR switch to InfluxDB
```

**Frontend:**
```bash
npm install laravel-echo pusher-js              # WebSocket client
npm install socket.io-client                    # Alternative
npm install chart.js chartjs-adapter-date-fns  # Better charts
npm install date-fns                            # Date handling
```

**Infrastructure:**
- **Redis:** Queue management and caching
- **Supervisor:** Process monitoring
- **PostgreSQL 12+** or **MySQL 8+**
- Optional: **TimescaleDB** or **InfluxDB** for time-series

### Knowledge Requirements

- Understanding of meter communication protocols (Modbus, MQTT, etc.)
- Experience with message queues and async processing
- Familiarity with time-series data patterns
- Network troubleshooting skills

---

## Phase 1: Data Ingestion

### Goal
Receive and store meter readings from physical equipment.

### 1.1 Database Schema

Create migration:

```php
// database/migrations/xxxx_create_meter_readings_table.php
Schema::create('meter_readings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meter_id')->constrained()->onDelete('cascade');
    $table->timestamp('reading_timestamp')->index();
    
    // Reading values
    $table->decimal('value', 15, 4);
    $table->string('unit', 20); // kWh, kW, V, A, etc.
    $table->string('parameter', 50); // consumption, voltage, current, etc.
    
    // Data quality
    $table->enum('quality', ['good', 'estimated', 'questionable', 'bad'])->default('good');
    $table->text('quality_notes')->nullable();
    
    // Source tracking
    $table->foreignId('gateway_id')->nullable()->constrained();
    $table->string('source_reference')->nullable(); // External ID
    
    $table->timestamps();
    
    // Composite index for common queries
    $table->index(['meter_id', 'reading_timestamp', 'parameter']);
    $table->index(['gateway_id', 'reading_timestamp']);
});

// Aggregate tables for performance
Schema::create('meter_readings_hourly', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meter_id')->constrained();
    $table->timestamp('hour_start');
    $table->string('parameter', 50);
    $table->decimal('avg_value', 15, 4);
    $table->decimal('min_value', 15, 4);
    $table->decimal('max_value', 15, 4);
    $table->decimal('sum_value', 15, 4);
    $table->integer('reading_count');
    $table->timestamps();
    
    $table->unique(['meter_id', 'hour_start', 'parameter']);
});

// Connection status tracking
Schema::create('gateway_connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gateway_id')->constrained();
    $table->timestamp('last_ping');
    $table->enum('status', ['online', 'offline', 'degraded'])->default('offline');
    $table->integer('missed_pings')->default(0);
    $table->json('connection_details')->nullable(); // IP, signal strength, etc.
    $table->timestamps();
});

// Data quality logs
Schema::create('data_quality_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meter_reading_id')->nullable()->constrained();
    $table->foreignId('meter_id')->constrained();
    $table->foreignId('gateway_id')->nullable()->constrained();
    $table->enum('issue_type', [
        'missing_data',
        'duplicate_data',
        'out_of_range',
        'communication_error',
        'calibration_drift'
    ]);
    $table->enum('severity', ['info', 'warning', 'error', 'critical']);
    $table->text('description');
    $table->json('metadata')->nullable();
    $table->timestamp('detected_at');
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    $table->index(['meter_id', 'detected_at']);
    $table->index(['severity', 'resolved_at']);
});
```

### 1.2 Data Ingestion API

Create API endpoint for receiving readings:

```php
// routes/api.php
Route::post('/v1/readings/ingest', [ReadingIngestionController::class, 'ingest'])
    ->middleware(['api', 'throttle:1000,1']); // 1000 requests per minute

// app/Http/Controllers/Api/ReadingIngestionController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMeterReading;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReadingIngestionController extends Controller
{
    /**
     * Ingest meter readings from gateways
     * 
     * Accepts batch or single readings
     */
    public function ingest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway_serial' => 'required|string|exists:gateways,serial_number',
            'timestamp' => 'required|date',
            'readings' => 'required|array|min:1',
            'readings.*.meter_id' => 'required|exists:meters,id',
            'readings.*.parameter' => 'required|string|in:consumption,voltage,current,power,power_factor',
            'readings.*.value' => 'required|numeric',
            'readings.*.unit' => 'required|string',
            'readings.*.quality' => 'nullable|in:good,estimated,questionable,bad',
        ]);

        if ($validator->fails()) {
            Log::warning('Invalid reading ingestion', [
                'errors' => $validator->errors(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find gateway
        $gateway = Gateway::where('serial_number', $request->gateway_serial)->first();
        
        // Update gateway connection status
        $gateway->connections()->updateOrCreate(
            ['gateway_id' => $gateway->id],
            [
                'last_ping' => now(),
                'status' => 'online',
                'missed_pings' => 0,
            ]
        );

        // Queue readings for processing
        $batchId = uniqid('batch-');
        foreach ($request->readings as $reading) {
            ProcessMeterReading::dispatch(
                $gateway->id,
                $reading,
                $request->timestamp,
                $batchId
            );
        }

        Log::info('Readings queued for processing', [
            'gateway' => $gateway->serial_number,
            'batch_id' => $batchId,
            'count' => count($request->readings)
        ]);

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'queued_count' => count($request->readings)
        ]);
    }
    
    /**
     * Health check endpoint for gateways
     */
    public function ping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway_serial' => 'required|string|exists:gateways,serial_number',
            'status' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $gateway = Gateway::where('serial_number', $request->gateway_serial)->first();
        
        $gateway->connections()->updateOrCreate(
            ['gateway_id' => $gateway->id],
            [
                'last_ping' => now(),
                'status' => 'online',
                'missed_pings' => 0,
                'connection_details' => $request->status,
            ]
        );

        return response()->json(['success' => true, 'server_time' => now()]);
    }
}
```

### 1.3 Processing Job

```php
// app/Jobs/ProcessMeterReading.php
<?php

namespace App\Jobs;

use App\Events\MeterReadingReceived;
use App\Models\DataQualityLog;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMeterReading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        private int $gatewayId,
        private array $reading,
        private string $timestamp,
        private string $batchId
    ) {}

    public function handle()
    {
        DB::transaction(function () {
            $meter = Meter::findOrFail($this->reading['meter_id']);
            
            // Validate reading value range
            $quality = $this->reading['quality'] ?? 'good';
            $qualityNotes = null;
            
            if ($this->isOutOfRange($this->reading)) {
                $quality = 'questionable';
                $qualityNotes = 'Value outside expected range';
                
                // Log data quality issue
                DataQualityLog::create([
                    'meter_id' => $meter->id,
                    'gateway_id' => $this->gatewayId,
                    'issue_type' => 'out_of_range',
                    'severity' => 'warning',
                    'description' => "Reading value {$this->reading['value']} is outside normal range",
                    'detected_at' => $this->timestamp,
                ]);
            }
            
            // Check for duplicate readings
            $exists = MeterReading::where('meter_id', $meter->id)
                ->where('reading_timestamp', $this->timestamp)
                ->where('parameter', $this->reading['parameter'])
                ->exists();
            
            if ($exists) {
                Log::warning('Duplicate reading detected', [
                    'meter_id' => $meter->id,
                    'timestamp' => $this->timestamp,
                    'parameter' => $this->reading['parameter']
                ]);
                return;
            }
            
            // Store reading
            $meterReading = MeterReading::create([
                'meter_id' => $meter->id,
                'gateway_id' => $this->gatewayId,
                'reading_timestamp' => $this->timestamp,
                'parameter' => $this->reading['parameter'],
                'value' => $this->reading['value'],
                'unit' => $this->reading['unit'],
                'quality' => $quality,
                'quality_notes' => $qualityNotes,
            ]);
            
            // Update meter last_log_update
            $meter->update(['last_log_update' => $this->timestamp]);
            
            // Broadcast event for real-time updates
            event(new MeterReadingReceived($meter, $meterReading));
            
            Log::info('Reading processed', [
                'meter' => $meter->name,
                'parameter' => $this->reading['parameter'],
                'value' => $this->reading['value'],
                'batch_id' => $this->batchId
            ]);
        });
    }
    
    private function isOutOfRange(array $reading): bool
    {
        // Define acceptable ranges per parameter
        $ranges = [
            'consumption' => ['min' => 0, 'max' => 100000], // kWh
            'voltage' => ['min' => 180, 'max' => 260], // Volts
            'current' => ['min' => 0, 'max' => 5000], // Amps
            'power' => ['min' => 0, 'max' => 1000000], // Watts
            'power_factor' => ['min' => 0, 'max' => 1],
        ];
        
        $parameter = $reading['parameter'];
        $value = $reading['value'];
        
        if (!isset($ranges[$parameter])) {
            return false;
        }
        
        return $value < $ranges[$parameter]['min'] || $value > $ranges[$parameter]['max'];
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Failed to process meter reading', [
            'gateway_id' => $this->gatewayId,
            'reading' => $this->reading,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### 1.4 MQTT Integration (Optional)

If using MQTT protocol:

```php
// app/Console/Commands/MqttMeterListener.php
<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMeterReading;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttMeterListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen for MQTT meter readings';

    public function handle()
    {
        $server = config('services.mqtt.host');
        $port = config('services.mqtt.port');
        $clientId = 'camr-listener-' . uniqid();
        
        $connectionSettings = (new ConnectionSettings())
            ->setUsername(config('services.mqtt.username'))
            ->setPassword(config('services.mqtt.password'))
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('camr/listener/status')
            ->setLastWillMessage('offline')
            ->setLastWillQualityOfService(1);
        
        $mqtt = new MqttClient($server, $port, $clientId);
        
        $this->info("Connecting to MQTT broker: {$server}:{$port}");
        $mqtt->connect($connectionSettings, true);
        
        $this->info("Connected! Subscribing to topics...");
        
        // Subscribe to all meter readings
        $mqtt->subscribe('camr/gateway/+/meter/+/reading', function ($topic, $message) {
            $this->processMessage($topic, $message);
        }, 0);
        
        $this->info("Listening for messages... Press Ctrl+C to stop.");
        
        $mqtt->loop(true);
        $mqtt->disconnect();
    }
    
    private function processMessage(string $topic, string $message)
    {
        try {
            // Parse topic: camr/gateway/{serial}/meter/{id}/reading
            preg_match('/camr\/gateway\/(.+?)\/meter\/(.+?)\/reading/', $topic, $matches);
            $gatewaySerial = $matches[1] ?? null;
            $meterId = $matches[2] ?? null;
            
            if (!$gatewaySerial || !$meterId) {
                $this->error("Invalid topic format: {$topic}");
                return;
            }
            
            $data = json_decode($message, true);
            
            if (!$data) {
                $this->error("Invalid JSON: {$message}");
                return;
            }
            
            // Find gateway
            $gateway = \App\Models\Gateway::where('serial_number', $gatewaySerial)->first();
            
            if (!$gateway) {
                $this->error("Gateway not found: {$gatewaySerial}");
                return;
            }
            
            // Queue for processing
            ProcessMeterReading::dispatch(
                $gateway->id,
                $data,
                $data['timestamp'] ?? now()->toIso8601String(),
                'mqtt-' . uniqid()
            );
            
            $this->info("Queued: {$gatewaySerial} → Meter {$meterId}");
            
        } catch (\Exception $e) {
            $this->error("Error processing message: {$e->getMessage()}");
        }
    }
}
```

### 1.5 Gateway Monitoring

Create scheduled task to check for offline gateways:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Check gateway connections every 5 minutes
    $schedule->call(function () {
        $offlineThreshold = now()->subMinutes(10);
        
        \App\Models\Gateway::whereHas('connections', function ($query) use ($offlineThreshold) {
            $query->where('last_ping', '<', $offlineThreshold)
                  ->where('status', '!=', 'offline');
        })->chunk(100, function ($gateways) {
            foreach ($gateways as $gateway) {
                $gateway->connections()->update([
                    'status' => 'offline',
                    'missed_pings' => DB::raw('missed_pings + 1')
                ]);
                
                // Trigger offline alert
                event(new \App\Events\GatewayOffline($gateway));
            }
        });
    })->everyFiveMinutes();
    
    // Aggregate hourly data
    $schedule->call(function () {
        \App\Jobs\AggregateHourlyReadings::dispatch(now()->subHour());
    })->hourly();
}
```

---

## Phase 2: Real-Time Updates

### Goal
Push live meter readings to connected dashboard users via WebSockets.

### 2.1 Install Laravel WebSockets

```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider"
php artisan migrate
```

Configure `.env`:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=camr-local
PUSHER_APP_KEY=camr-websocket-key
PUSHER_APP_SECRET=camr-websocket-secret
PUSHER_APP_CLUSTER=mt1

# WebSockets will run on this port
LARAVEL_WEBSOCKETS_PORT=6001
```

Update `config/broadcasting.php`:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'host' => '127.0.0.1',
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
        'scheme' => 'http',
        'encrypted' => false,
        'useTLS' => false,
    ],
],
```

### 2.2 Create Events

```php
// app/Events/MeterReadingReceived.php
<?php

namespace App\Events;

use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeterReadingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Meter $meter,
        public MeterReading $reading
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('meter.' . $this->meter->id),
            new Channel('site.' . $this->meter->gateway->site_id),
            new Channel('dashboard'), // Global dashboard updates
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'reading.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'meter_id' => $this->meter->id,
            'meter_name' => $this->meter->name,
            'parameter' => $this->reading->parameter,
            'value' => $this->reading->value,
            'unit' => $this->reading->unit,
            'quality' => $this->reading->quality,
            'timestamp' => $this->reading->reading_timestamp->toIso8601String(),
        ];
    }
}

// app/Events/GatewayOffline.php
class GatewayOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Gateway $gateway) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('site.' . $this->gateway->site_id),
            new Channel('dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'gateway.offline';
    }

    public function broadcastWith(): array
    {
        return [
            'gateway_id' => $this->gateway->id,
            'serial_number' => $this->gateway->serial_number,
            'site' => $this->gateway->site->code,
        ];
    }
}
```

### 2.3 Frontend Setup

Install Laravel Echo:

```bash
npm install --save laravel-echo pusher-js
```

Configure Echo (`resources/js/bootstrap.ts`):

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});
```

Update `.env` for frontend:

```env
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_PORT="${LARAVEL_WEBSOCKETS_PORT}"
```

### 2.4 Real-Time Composable

```typescript
// resources/js/composables/useRealtimeMeter.ts
import { ref, onMounted, onUnmounted } from 'vue';

export interface MeterReading {
    meter_id: number;
    meter_name: string;
    parameter: string;
    value: number;
    unit: string;
    quality: string;
    timestamp: string;
}

export function useRealtimeMeter(meterId: number) {
    const latestReading = ref<MeterReading | null>(null);
    const isConnected = ref(false);
    const readings = ref<MeterReading[]>([]);
    
    let channel: any = null;

    const connect = () => {
        channel = window.Echo.channel(`meter.${meterId}`)
            .listen('.reading.received', (data: MeterReading) => {
                latestReading.value = data;
                readings.value.unshift(data);
                
                // Keep only last 100 readings in memory
                if (readings.value.length > 100) {
                    readings.value = readings.value.slice(0, 100);
                }
            });

        window.Echo.connector.pusher.connection.bind('connected', () => {
            isConnected.value = true;
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            isConnected.value = false;
        });
    };

    const disconnect = () => {
        if (channel) {
            window.Echo.leave(`meter.${meterId}`);
            channel = null;
        }
    };

    onMounted(() => {
        connect();
    });

    onUnmounted(() => {
        disconnect();
    });

    return {
        latestReading,
        readings,
        isConnected,
        connect,
        disconnect,
    };
}
```

### 2.5 Real-Time Dashboard Component

```vue
<!-- resources/js/components/RealtimeMeterCard.vue -->
<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Activity, Zap } from 'lucide-vue-next';
import { useRealtimeMeter } from '@/composables/useRealtimeMeter';

interface Props {
    meterId: number;
    meterName: string;
}

const props = defineProps<Props>();

const { latestReading, isConnected, readings } = useRealtimeMeter(props.meterId);

const currentValue = computed(() => {
    if (!latestReading.value) return 'N/A';
    return `${latestReading.value.value.toFixed(2)} ${latestReading.value.unit}`;
});

const qualityColor = computed(() => {
    if (!latestReading.value) return 'bg-gray-500';
    const quality = latestReading.value.quality;
    return quality === 'good' ? 'bg-green-500' : 
           quality === 'estimated' ? 'bg-yellow-500' : 
           quality === 'questionable' ? 'bg-orange-500' : 'bg-red-500';
});

const lastUpdate = computed(() => {
    if (!latestReading.value) return 'Never';
    const date = new Date(latestReading.value.timestamp);
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
    return seconds < 60 ? `${seconds}s ago` : 
           seconds < 3600 ? `${Math.floor(seconds / 60)}m ago` : 
           `${Math.floor(seconds / 3600)}h ago`;
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <Zap class="h-5 w-5" />
                        {{ meterName }}
                    </CardTitle>
                    <CardDescription>Live reading</CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <Activity 
                        :class="[
                            'h-4 w-4',
                            isConnected ? 'text-green-600 animate-pulse' : 'text-gray-400'
                        ]"
                    />
                    <Badge :class="qualityColor" variant="outline">
                        {{ latestReading?.quality || 'Unknown' }}
                    </Badge>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div>
                    <div class="text-3xl font-bold">{{ currentValue }}</div>
                    <div class="text-sm text-muted-foreground">{{ lastUpdate }}</div>
                </div>
                
                <div v-if="readings.length > 0" class="text-xs text-muted-foreground">
                    {{ readings.length }} readings received
                </div>
            </div>
        </CardContent>
    </Card>
</template>
```

### 2.6 Start WebSocket Server

Add to Supervisor config:

```ini
; /etc/supervisor/conf.d/camr-websockets.conf
[program:camr-websockets]
command=php /path/to/camr/artisan websockets:serve
directory=/path/to/camr
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/camr-websockets.log
```

Start manually for testing:

```bash
php artisan websockets:serve
```

---

## Phase 3: Data Visualization

### Goal
Advanced charts and visualizations for meter data analysis.

### 3.1 Enhanced Chart Component

```vue
<!-- resources/js/components/AdvancedMeterChart.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    TimeScale,
    Filler
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { useRealtimeMeter } from '@/composables/useRealtimeMeter';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    TimeScale,
    Filler
);

interface Props {
    meterId: number;
    parameter: string;
    historicalData: Array<{ timestamp: string; value: number }>;
    realtime?: boolean;
}

const props = defineProps<Props>();

const chartData = ref({
    labels: [] as string[],
    datasets: [{
        label: props.parameter,
        data: [] as number[],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
    }]
});

// Add real-time updates if enabled
if (props.realtime) {
    const { latestReading } = useRealtimeMeter(props.meterId);
    
    watch(latestReading, (reading) => {
        if (reading && reading.parameter === props.parameter) {
            chartData.value.labels.push(reading.timestamp);
            chartData.value.datasets[0].data.push(reading.value);
            
            // Keep only last 50 points for performance
            if (chartData.value.labels.length > 50) {
                chartData.value.labels.shift();
                chartData.value.datasets[0].data.shift();
            }
        }
    });
}

// Initialize with historical data
chartData.value.labels = props.historicalData.map(d => d.timestamp);
chartData.value.datasets[0].data = props.historicalData.map(d => d.value);

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            type: 'time',
            time: {
                unit: 'minute',
            },
        },
        y: {
            beginAtZero: true,
        }
    },
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
    },
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
    },
};
</script>

<template>
    <div class="h-[400px]">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
```

### 3.2 Load Profile Visualization

```vue
<!-- resources/js/components/LoadProfileChart.vue -->
<script setup lang="ts">
// 24-hour load curve showing typical consumption pattern
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

interface Props {
    data: Array<{ hour: number; avgLoad: number; peakLoad: number }>;
}

const props = defineProps<Props>();

const chartData = computed(() => ({
    labels: props.data.map(d => `${d.hour}:00`),
    datasets: [
        {
            label: 'Average Load',
            data: props.data.map(d => d.avgLoad),
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
        },
        {
            label: 'Peak Load',
            data: props.data.map(d => d.peakLoad),
            backgroundColor: 'rgba(239, 68, 68, 0.5)',
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: 'Load (kW)'
            }
        },
        x: {
            title: {
                display: true,
                text: 'Hour of Day'
            }
        }
    }
};
</script>

<template>
    <div class="h-[400px]">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
```

---

## Phase 4: Alert System

### Goal
Automated detection and notification of abnormal readings.

### 4.1 Alert Rules Schema

```php
// database/migrations/xxxx_create_alert_rules_table.php
Schema::create('alert_rules', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    
    // Scope
    $table->foreignId('meter_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('parameter'); // consumption, voltage, etc.
    
    // Condition
    $table->enum('condition_type', [
        'threshold_exceeded',
        'threshold_below',
        'rate_of_change',
        'no_data',
        'data_quality'
    ]);
    $table->decimal('threshold_value', 15, 4)->nullable();
    $table->integer('duration_minutes')->default(5); // Sustained for X minutes
    
    // Severity
    $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('warning');
    
    // Notification
    $table->json('notification_channels'); // ['mail', 'database', 'slack', 'sms']
    $table->json('recipient_user_ids')->nullable();
    $table->string('recipient_emails')->nullable();
    
    // Cooldown (prevent alert spam)
    $table->integer('cooldown_minutes')->default(60);
    
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('alert_rule_id')->constrained()->onDelete('cascade');
    $table->foreignId('meter_id')->nullable()->constrained();
    $table->foreignId('site_id')->nullable()->constrained();
    
    $table->enum('severity', ['info', 'warning', 'error', 'critical']);
    $table->string('title');
    $table->text('message');
    $table->json('metadata')->nullable(); // Reading values, etc.
    
    // State
    $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
    $table->timestamp('triggered_at');
    $table->timestamp('acknowledged_at')->nullable();
    $table->foreignId('acknowledged_by')->nullable()->constrained('users');
    $table->text('acknowledgement_note')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->text('resolution_note')->nullable();
    
    // Escalation
    $table->boolean('escalated')->default(false);
    $table->timestamp('escalated_at')->nullable();
    
    $table->timestamps();
    
    $table->index(['status', 'severity']);
    $table->index(['triggered_at']);
});
```

### 4.2 Alert Rule Model

```php
// app/Models/AlertRule.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'meter_id',
        'site_id',
        'parameter',
        'condition_type',
        'threshold_value',
        'duration_minutes',
        'severity',
        'notification_channels',
        'recipient_user_ids',
        'recipient_emails',
        'cooldown_minutes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'threshold_value' => 'decimal:4',
        'notification_channels' => 'array',
        'recipient_user_ids' => 'array',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Check if rule applies to a given reading
     */
    public function evaluate(MeterReading $reading): bool
    {
        // Only check if rule is active
        if (!$this->is_active) {
            return false;
        }

        // Check if rule applies to this meter/parameter
        if ($this->meter_id && $this->meter_id !== $reading->meter_id) {
            return false;
        }

        if ($this->parameter !== $reading->parameter) {
            return false;
        }

        // Check cooldown - don't trigger if recently alerted
        $recentAlert = $this->alerts()
            ->where('status', 'active')
            ->where('triggered_at', '>', now()->subMinutes($this->cooldown_minutes))
            ->exists();

        if ($recentAlert) {
            return false;
        }

        // Evaluate condition
        return match ($this->condition_type) {
            'threshold_exceeded' => $reading->value > $this->threshold_value,
            'threshold_below' => $reading->value < $this->threshold_value,
            'rate_of_change' => $this->checkRateOfChange($reading),
            'no_data' => $this->checkNoData($reading),
            'data_quality' => $reading->quality !== 'good',
            default => false,
        };
    }

    private function checkRateOfChange(MeterReading $reading): bool
    {
        // Check if value changed too quickly
        $previousReading = MeterReading::where('meter_id', $reading->meter_id)
            ->where('parameter', $reading->parameter)
            ->where('reading_timestamp', '<', $reading->reading_timestamp)
            ->orderBy('reading_timestamp', 'desc')
            ->first();

        if (!$previousReading) {
            return false;
        }

        $valueChange = abs($reading->value - $previousReading->value);
        $timeChange = $reading->reading_timestamp->diffInMinutes($previousReading->reading_timestamp);

        if ($timeChange == 0) {
            return false;
        }

        $rateOfChange = $valueChange / $timeChange;

        return $rateOfChange > $this->threshold_value;
    }

    private function checkNoData(MeterReading $reading): bool
    {
        // This would be triggered by scheduled job, not by reading
        return false;
    }
}
```

### 4.3 Alert Checking Job

```php
// app/Jobs/CheckAlertRules.php
<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\MeterReading;
use App\Notifications\AlertTriggered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class CheckAlertRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private MeterReading $reading
    ) {}

    public function handle()
    {
        // Get all active rules that might apply
        $rules = AlertRule::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('meter_id')
                      ->orWhere('meter_id', $this->reading->meter_id);
            })
            ->where('parameter', $this->reading->parameter)
            ->get();

        foreach ($rules as $rule) {
            if ($rule->evaluate($this->reading)) {
                $this->triggerAlert($rule);
            }
        }
    }

    private function triggerAlert(AlertRule $rule)
    {
        $alert = Alert::create([
            'alert_rule_id' => $rule->id,
            'meter_id' => $this->reading->meter_id,
            'site_id' => $this->reading->meter->gateway->site_id,
            'severity' => $rule->severity,
            'title' => $rule->name,
            'message' => $this->buildAlertMessage($rule),
            'metadata' => [
                'reading_id' => $this->reading->id,
                'value' => $this->reading->value,
                'threshold' => $rule->threshold_value,
                'parameter' => $this->reading->parameter,
            ],
            'triggered_at' => now(),
            'status' => 'active',
        ]);

        // Send notifications
        $this->sendNotifications($alert, $rule);

        // Broadcast event
        event(new \App\Events\AlertTriggered($alert));
    }

    private function buildAlertMessage(AlertRule $rule): string
    {
        $meter = $this->reading->meter;
        
        return match ($rule->condition_type) {
            'threshold_exceeded' => "{$meter->name} {$this->reading->parameter} ({$this->reading->value} {$this->reading->unit}) exceeded threshold of {$rule->threshold_value}",
            'threshold_below' => "{$meter->name} {$this->reading->parameter} ({$this->reading->value} {$this->reading->unit}) fell below threshold of {$rule->threshold_value}",
            'data_quality' => "{$meter->name} has {$this->reading->quality} quality data",
            default => "{$meter->name} triggered alert rule: {$rule->name}",
        };
    }

    private function sendNotifications(Alert $alert, AlertRule $rule)
    {
        $channels = $rule->notification_channels;
        
        // Get recipients
        $users = [];
        if ($rule->recipient_user_ids) {
            $users = \App\Models\User::whereIn('id', $rule->recipient_user_ids)->get();
        }

        // Send via configured channels
        if (!empty($users)) {
            Notification::send($users, new AlertTriggered($alert));
        }

        // Send to additional emails
        if ($rule->recipient_emails) {
            $emails = explode(',', $rule->recipient_emails);
            foreach ($emails as $email) {
                Notification::route('mail', trim($email))
                    ->notify(new AlertTriggered($alert));
            }
        }
    }
}
```

Update ProcessMeterReading to check alerts:

```php
// In app/Jobs/ProcessMeterReading.php, add after storing reading:

// Check alert rules
CheckAlertRules::dispatch($meterReading);
```

### 4.4 Alert Notification

```php
// app/Notifications/AlertTriggered.php
<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class AlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Alert $alert
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'slack'];
    }

    public function toMail($notifiable): MailMessage
    {
        $severityColor = match ($this->alert->severity) {
            'critical' => 'red',
            'error' => 'orange',
            'warning' => 'yellow',
            default => 'blue',
        };

        return (new MailMessage)
            ->subject("[{$this->alert->severity}] CAMR Alert: {$this->alert->title}")
            ->greeting("⚠️ {$this->alert->title}")
            ->line($this->alert->message)
            ->line("**Severity:** " . ucfirst($this->alert->severity))
            ->line("**Meter:** {$this->alert->meter->name}")
            ->line("**Site:** {$this->alert->site->code}")
            ->line("**Triggered at:** {$this->alert->triggered_at->format('Y-m-d H:i:s')}")
            ->action('View Alert', route('alerts.show', $this->alert))
            ->line('Please acknowledge or resolve this alert as soon as possible.');
    }

    public function toArray($notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'severity' => $this->alert->severity,
            'meter_id' => $this->alert->meter_id,
            'site_id' => $this->alert->site_id,
            'triggered_at' => $this->alert->triggered_at,
        ];
    }

    public function toSlack($notifiable): SlackMessage
    {
        $emoji = match ($this->alert->severity) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        return (new SlackMessage)
            ->error()
            ->content("{$emoji} **CAMR Alert**")
            ->attachment(function ($attachment) {
                $attachment
                    ->title($this->alert->title)
                    ->content($this->alert->message)
                    ->fields([
                        'Severity' => ucfirst($this->alert->severity),
                        'Meter' => $this->alert->meter->name,
                        'Site' => $this->alert->site->code,
                        'Time' => $this->alert->triggered_at->format('Y-m-d H:i:s'),
                    ]);
            });
    }
}
```

### 4.5 Alert Dashboard UI

```vue
<!-- resources/js/pages/alerts/Index.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, CheckCircle, Clock } from 'lucide-vue-next';

interface Alert {
    id: number;
    title: string;
    message: string;
    severity: 'info' | 'warning' | 'error' | 'critical';
    status: 'active' | 'acknowledged' | 'resolved';
    triggered_at: string;
    meter: { id: number; name: string };
    site: { id: number; code: string };
}

interface Props {
    alerts: {
        data: Alert[];
        total: number;
    };
}

const props = defineProps<Props>();

const getSeverityColor = (severity: string) => {
    const colors = {
        critical: 'bg-red-600',
        error: 'bg-orange-600',
        warning: 'bg-yellow-600',
        info: 'bg-blue-600',
    };
    return colors[severity] || 'bg-gray-600';
};

const acknowledgeAlert = (alertId: number) => {
    router.post(`/alerts/${alertId}/acknowledge`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification handled by flash
        }
    });
};

const resolveAlert = (alertId: number) => {
    router.post(`/alerts/${alertId}/resolve`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification handled by flash
        }
    });
};
</script>

<template>
    <Head title="Alerts" />
    
    <AppLayout>
        <div class="container mx-auto py-6 space-y-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Alerts</h1>
                <p class="text-muted-foreground">
                    Monitor and manage system alerts
                </p>
            </div>

            <div class="grid gap-4">
                <Card v-for="alert in props.alerts.data" :key="alert.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <AlertCircle :class="[
                                    'h-6 w-6',
                                    alert.severity === 'critical' ? 'text-red-600' : 
                                    alert.severity === 'error' ? 'text-orange-600' : 
                                    alert.severity === 'warning' ? 'text-yellow-600' : 'text-blue-600'
                                ]" />
                                <div>
                                    <CardTitle>{{ alert.title }}</CardTitle>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :class="getSeverityColor(alert.severity)">
                                            {{ alert.severity.toUpperCase() }}
                                        </Badge>
                                        <Badge variant="outline">{{ alert.site.code }}</Badge>
                                        <span class="text-sm text-muted-foreground">
                                            {{ alert.meter.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="alert.status === 'active'"
                                    size="sm"
                                    variant="outline"
                                    @click="acknowledgeAlert(alert.id)"
                                >
                                    <Clock class="h-4 w-4 mr-2" />
                                    Acknowledge
                                </Button>
                                <Button
                                    v-if="alert.status !== 'resolved'"
                                    size="sm"
                                    @click="resolveAlert(alert.id)"
                                >
                                    <CheckCircle class="h-4 w-4 mr-2" />
                                    Resolve
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm">{{ alert.message }}</p>
                        <p class="text-xs text-muted-foreground mt-2">
                            Triggered at {{ new Date(alert.triggered_at).toLocaleString() }}
                        </p>
                    </CardContent>
                </Card>

                <div v-if="props.alerts.data.length === 0" class="text-center py-12">
                    <CheckCircle class="h-16 w-16 mx-auto text-green-600 mb-4" />
                    <h3 class="text-lg font-medium">No active alerts</h3>
                    <p class="text-sm text-muted-foreground">
                        All systems are operating normally
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
```

---

## Database Schema

### Complete Schema Overview

```sql
-- Existing tables (already created)
sites, gateways, meters, users, buildings, locations, config_files

-- New tables for real-world integration

-- Meter readings (raw data)
meter_readings (
    id, meter_id, gateway_id, reading_timestamp,
    parameter, value, unit, quality, quality_notes,
    source_reference, created_at, updated_at
)

-- Aggregated readings for performance
meter_readings_hourly (
    id, meter_id, hour_start, parameter,
    avg_value, min_value, max_value, sum_value,
    reading_count, created_at, updated_at
)

meter_readings_daily (
    id, meter_id, day_start, parameter,
    avg_value, min_value, max_value, sum_value,
    reading_count, created_at, updated_at
)

-- Gateway connection monitoring
gateway_connections (
    id, gateway_id, last_ping, status, missed_pings,
    connection_details, created_at, updated_at
)

-- Data quality tracking
data_quality_logs (
    id, meter_reading_id, meter_id, gateway_id,
    issue_type, severity, description, metadata,
    detected_at, resolved_at, created_at, updated_at
)

-- Alert system
alert_rules (
    id, name, description, is_active,
    meter_id, site_id, parameter,
    condition_type, threshold_value, duration_minutes,
    severity, notification_channels,
    recipient_user_ids, recipient_emails,
    cooldown_minutes, created_at, updated_at, deleted_at
)

alerts (
    id, alert_rule_id, meter_id, site_id,
    severity, title, message, metadata,
    status, triggered_at, acknowledged_at, acknowledged_by,
    acknowledgement_note, resolved_at, resolution_note,
    escalated, escalated_at, created_at, updated_at
)
```

---

## API Specifications

### Ingest Reading API

**Endpoint:** `POST /api/v1/readings/ingest`

**Authentication:** API Token or Gateway Certificate

**Request:**
```json
{
  "gateway_serial": "GW-001234",
  "timestamp": "2025-11-17T10:30:00Z",
  "readings": [
    {
      "meter_id": 123,
      "parameter": "consumption",
      "value": 145.67,
      "unit": "kWh",
      "quality": "good"
    },
    {
      "meter_id": 123,
      "parameter": "voltage",
      "value": 230.5,
      "unit": "V",
      "quality": "good"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "batch_id": "batch-abc123",
  "queued_count": 2
}
```

### Gateway Ping API

**Endpoint:** `POST /api/v1/gateway/ping`

**Request:**
```json
{
  "gateway_serial": "GW-001234",
  "status": {
    "ip_address": "192.168.1.100",
    "signal_strength": 85,
    "connected_meters": 15,
    "memory_usage": 45.2,
    "cpu_usage": 12.3
  }
}
```

**Response:**
```json
{
  "success": true,
  "server_time": "2025-11-17T10:30:05Z"
}
```

### Retrieve Readings API

**Endpoint:** `GET /api/v1/meters/{meter}/readings`

**Query Parameters:**
- `start_date` (ISO 8601)
- `end_date` (ISO 8601)
- `parameter` (consumption, voltage, etc.)
- `aggregation` (raw, hourly, daily)

**Response:**
```json
{
  "meter_id": 123,
  "meter_name": "Main Building - Meter 01",
  "parameter": "consumption",
  "aggregation": "hourly",
  "readings": [
    {
      "timestamp": "2025-11-17T10:00:00Z",
      "value": 145.67,
      "unit": "kWh",
      "quality": "good"
    }
  ]
}
```

---

## Security Considerations

### API Authentication

```php
// config/sanctum.php - Add to stateful domains
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

// Create API tokens for gateways
$gateway = Gateway::find(1);
$token = $gateway->user->createToken('gateway-token', ['ingest-readings'])->plainTextToken;
```

### Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':1000,1', // 1000 per minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### Data Validation

- **Input sanitization:** Validate all incoming readings
- **Range checks:** Reject impossible values (e.g., negative consumption)
- **Timestamp validation:** Reject future timestamps or very old data
- **Duplicate detection:** Check for duplicate readings
- **SQL injection prevention:** Use parameterized queries

### Network Security

- **TLS/SSL:** Use HTTPS for all API endpoints
- **Certificates:** Mutual TLS for gateway authentication
- **VPN:** Consider VPN for gateway-to-server communication
- **Firewall:** Restrict API access to known IP ranges

---

## Testing Strategy

### 1. Unit Tests

```php
// tests/Unit/AlertRuleTest.php
public function test_threshold_exceeded_rule_evaluates_correctly()
{
    $rule = AlertRule::factory()->create([
        'condition_type' => 'threshold_exceeded',
        'threshold_value' => 100,
        'parameter' => 'consumption',
    ]);

    $reading = MeterReading::factory()->create([
        'value' => 150,
        'parameter' => 'consumption',
    ]);

    $this->assertTrue($rule->evaluate($reading));
}
```

### 2. Integration Tests

```php
// tests/Feature/ReadingIngestionTest.php
public function test_can_ingest_meter_readings()
{
    $gateway = Gateway::factory()->create();
    $meter = Meter::factory()->create();

    $response = $this->postJson('/api/v1/readings/ingest', [
        'gateway_serial' => $gateway->serial_number,
        'timestamp' => now()->toIso8601String(),
        'readings' => [
            [
                'meter_id' => $meter->id,
                'parameter' => 'consumption',
                'value' => 145.67,
                'unit' => 'kWh',
            ],
        ],
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 145.67,
    ]);
}
```

### 3. Load Testing

```bash
# Use Apache Bench
ab -n 10000 -c 100 -p reading.json -T application/json \
   http://localhost/api/v1/readings/ingest

# Or use Artillery
artillery quick --count 100 --num 1000 \
   http://localhost/api/v1/readings/ingest
```

### 4. Mock Data Generation

```php
// Create command to generate fake readings
php artisan make:command GenerateFakeReadings

// In the command:
public function handle()
{
    $meters = Meter::all();
    
    for ($i = 0; $i < 100; $i++) {
        foreach ($meters as $meter) {
            MeterReading::create([
                'meter_id' => $meter->id,
                'gateway_id' => $meter->gateway_id,
                'reading_timestamp' => now()->subMinutes($i * 15),
                'parameter' => 'consumption',
                'value' => rand(50, 200) + (rand(0, 100) / 100),
                'unit' => 'kWh',
                'quality' => 'good',
            ]);
        }
    }
    
    $this->info('Generated 100 readings for each meter');
}
```

---

## Performance Optimization

### 1. Database Indexing

```php
// Ensure proper indexes exist
Schema::table('meter_readings', function (Blueprint $table) {
    $table->index(['meter_id', 'reading_timestamp']);
    $table->index(['gateway_id', 'reading_timestamp']);
    $table->index(['reading_timestamp', 'parameter']);
});
```

### 2. Query Optimization

```php
// Use chunking for large datasets
MeterReading::where('reading_timestamp', '<', now()->subDays(90))
    ->chunk(1000, function ($readings) {
        // Process or delete old readings
    });

// Eager load relationships
$meters = Meter::with(['gateway.site', 'latestReading'])->get();
```

### 3. Caching Strategy

```php
// Cache frequently accessed data
$siteStats = Cache::remember("site-{$siteId}-stats", 300, function () use ($siteId) {
    return [
        'total_consumption' => MeterReading::whereHas('meter.gateway', function ($q) use ($siteId) {
            $q->where('site_id', $siteId);
        })->where('parameter', 'consumption')
          ->where('reading_timestamp', '>', now()->subDay())
          ->sum('value'),
    ];
});
```

### 4. Queue Configuration

```php
// config/queue.php - Use Redis for better performance
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### 5. Data Aggregation Jobs

```php
// Aggregate old data to reduce storage
// Run nightly
Schedule::call(function () {
    AggregateOldReadings::dispatch(now()->subDays(7));
})->dailyAt('02:00');
```

---

## Troubleshooting

### Common Issues

#### 1. No Readings Received

**Symptoms:** Database empty, no readings coming in

**Checklist:**
- [ ] Gateway is powered on and connected to network
- [ ] Gateway can reach server (test with ping)
- [ ] API endpoint is accessible (test with curl)
- [ ] API authentication is working
- [ ] Queue workers are running (`php artisan queue:work`)
- [ ] Check Laravel logs: `storage/logs/laravel.log`
- [ ] Check gateway logs (if available)

**Debug:**
```bash
# Test API endpoint
curl -X POST http://localhost/api/v1/readings/ingest \
  -H "Content-Type: application/json" \
  -d '{"gateway_serial":"TEST","timestamp":"2025-11-17T10:00:00Z","readings":[{"meter_id":1,"parameter":"consumption","value":100,"unit":"kWh"}]}'

# Check queue
php artisan queue:listen --tries=3 --timeout=30 -vvv
```

#### 2. WebSocket Connection Failed

**Symptoms:** Real-time updates not working

**Checklist:**
- [ ] WebSocket server is running (`php artisan websockets:serve`)
- [ ] Port 6001 is open and accessible
- [ ] Frontend Echo configuration is correct
- [ ] Pusher credentials match in backend and frontend

**Debug:**
```bash
# Test WebSocket connection
wscat -c ws://localhost:6001/app/camr-websocket-key?protocol=7
```

#### 3. High Database Load

**Symptoms:** Slow queries, timeouts

**Solutions:**
- Enable query caching
- Add missing indexes
- Implement data aggregation
- Archive old data
- Consider time-series database

#### 4. Alerts Not Firing

**Symptoms:** No alerts despite abnormal readings

**Checklist:**
- [ ] Alert rules are active (`is_active = true`)
- [ ] CheckAlertRules job is being dispatched
- [ ] Alert cooldown period has expired
- [ ] Notification channels are configured
- [ ] Check failed jobs: `php artisan queue:failed`

#### 5. Duplicate Readings

**Symptoms:** Same reading stored multiple times

**Solutions:**
- Add unique constraint on (meter_id, reading_timestamp, parameter)
- Implement duplicate detection in ProcessMeterReading job
- Check gateway for retransmission issues

---

## Deployment Checklist

### Before Going Live

- [ ] **Database ready:** All migrations run, indexes created
- [ ] **Queue workers:** Supervisor configured and running
- [ ] **WebSocket server:** Running and monitored
- [ ] **Redis:** Installed and configured
- [ ] **Cron jobs:** Scheduled tasks configured
- [ ] **Monitoring:** Logging and error tracking set up
- [ ] **Backups:** Automated database backups
- [ ] **Security:** API rate limiting, authentication
- [ ] **Documentation:** API docs for gateway vendors
- [ ] **Testing:** Load testing completed
- [ ] **Alerting:** On-call rotation defined

### Gateway Configuration

Provide to gateway vendors/installers:

```
API Endpoint: https://your-domain.com/api/v1/readings/ingest
API Token: [generated-token]
Ping Interval: 60 seconds
Reading Interval: 900 seconds (15 minutes)
Batch Size: 50 readings
Timeout: 30 seconds
Retry: 3 attempts with exponential backoff
```

---

## Next Steps After Implementation

1. **Monitor Performance:** Track query times, queue depth, WebSocket connections
2. **Gather Feedback:** Get input from operators on alert thresholds
3. **Refine Rules:** Adjust alert rules based on false positive rate
4. **Optimize Queries:** Add indexes as needed based on slow query log
5. **Scale Infrastructure:** Add workers, cache servers as data grows
6. **Enhance Visualizations:** Add custom dashboards per user needs
7. **ML/AI:** Consider anomaly detection with machine learning

---

## Resources

### Documentation
- Laravel WebSockets: https://beyondco.de/docs/laravel-websockets
- Laravel Horizon: https://laravel.com/docs/horizon
- Laravel Echo: https://laravel.com/docs/broadcasting
- MQTT Protocol: https://mqtt.org/

### Tools
- Supervisor: http://supervisord.org/
- Redis: https://redis.io/
- TimescaleDB: https://www.timescale.com/
- InfluxDB: https://www.influxdata.com/

### Meter Vendors
- Itron: https://www.itron.com/
- Landis+Gyr: https://www.landisgyr.com/
- Schneider Electric: https://www.se.com/

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-17  
**Maintained By:** Development Team

**Note:** This is a living document. Update as implementation progresses and requirements evolve.
