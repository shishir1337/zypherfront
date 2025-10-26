<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZypherController extends Controller
{
    private $zypherApiUrl;

    public function __construct()
    {
        $this->zypherApiUrl = env('ZYPHER_API_URL', 'https://zypher.bigbuller.com/api');
    }

    /**
     * Show system status page
     */
    public function status()
    {
        $pageTitle = 'Zypher System Status';
        
        try {
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for self-signed certificates
            ])->timeout(10)->get("{$this->zypherApiUrl}/tradingview/status");
            
            $status = $response->successful() ? $response->json() : null;
            
            // Add debug info if connection fails
            if (!$status) {
                \Log::error('Zypher API Status Failed', [
                    'url' => "{$this->zypherApiUrl}/tradingview/status",
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Zypher API Connection Error', [
                'url' => "{$this->zypherApiUrl}/tradingview/status",
                'error' => $e->getMessage()
            ]);
            $status = null;
        }

        return view('admin.zypher.status', compact('pageTitle', 'status'));
    }

    /**
     * Show manual control page
     */
    public function manual()
    {
        $pageTitle = 'Zypher Manual Control';
        
        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->get("{$this->zypherApiUrl}/tradingview/status");
            $status = $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            $status = null;
        }

        return view('admin.zypher.manual', compact('pageTitle', 'status'));
    }

    /**
     * Apply manual control
     */
    public function applyManualControl(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down,neutral',
            'speed' => 'required|numeric|min:0.001|max:0.1',
            'intensity' => 'required|numeric|min:0.1|max:10',
            'duration_seconds' => 'required|integer|min:30|max:3600'
        ]);

        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->post("{$this->zypherApiUrl}/tradingview/manual-control", [
                'direction' => $request->direction,
                'speed' => (float) $request->speed,
                'intensity' => (float) $request->intensity,
                'duration_seconds' => (int) $request->duration_seconds
            ]);

            if ($response->successful()) {
                $notify[] = ['success', 'Manual control applied successfully'];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'Failed to apply manual control: ' . ($response->json()['error'] ?? 'Unknown error')];
                return back()->withNotify($notify);
            }
        } catch (\Exception $e) {
            $notify[] = ['error', 'Connection error: ' . $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    /**
     * Show trading mode page
     */
    public function mode()
    {
        $pageTitle = 'Zypher Trading Mode';
        
        try {
            $modeResponse = Http::withOptions(['verify' => false])->timeout(10)->get("{$this->zypherApiUrl}/tradingview/mode");
            $statusResponse = Http::withOptions(['verify' => false])->timeout(10)->get("{$this->zypherApiUrl}/tradingview/status");
            
            $currentMode = $modeResponse->successful() ? $modeResponse->json() : null;
            $status = $statusResponse->successful() ? $statusResponse->json() : null;
        } catch (\Exception $e) {
            $currentMode = null;
            $status = null;
        }

        return view('admin.zypher.mode', compact('pageTitle', 'currentMode', 'status'));
    }

    /**
     * Update trading mode
     */
    public function updateMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:auto,manual'
        ]);

        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->post("{$this->zypherApiUrl}/tradingview/mode", [
                'mode' => $request->mode
            ]);

            if ($response->successful()) {
                $notify[] = ['success', "Trading mode switched to {$request->mode} successfully"];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'Failed to update mode: ' . ($response->json()['error'] ?? 'Unknown error')];
                return back()->withNotify($notify);
            }
        } catch (\Exception $e) {
            $notify[] = ['error', 'Connection error: ' . $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    /**
     * Start trading service
     */
    public function start()
    {
        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->post("{$this->zypherApiUrl}/tradingview/start");

            if ($response->successful()) {
                $notify[] = ['success', 'Trading service started successfully'];
            } else {
                $notify[] = ['error', 'Failed to start service'];
            }
        } catch (\Exception $e) {
            $notify[] = ['error', 'Connection error: ' . $e->getMessage()];
        }

        return back()->withNotify($notify);
    }

    /**
     * Stop trading service
     */
    public function stop()
    {
        try {
            $response = Http::withOptions(['verify' => false])->timeout(10)->post("{$this->zypherApiUrl}/tradingview/stop");

            if ($response->successful()) {
                $notify[] = ['success', 'Trading service stopped successfully'];
            } else {
                $notify[] = ['error', 'Failed to stop service'];
            }
        } catch (\Exception $e) {
            $notify[] = ['error', 'Connection error: ' . $e->getMessage()];
        }

        return back()->withNotify($notify);
    }
}

