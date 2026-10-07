<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $ok = true;
        $errors = [];

        // DB check
        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $ok = false;
            // Endpoint público: el detalle va al log, no a la respuesta.
            Log::error('Healthcheck DB: '.$e->getMessage());
            $errors[] = 'db';
        }

        // Cache/Redis check (if configured)
        try {
            $cacheDriver = config('cache.default');
            if (in_array($cacheDriver, ['redis', 'memcached'])) {
                $res = Cache::store($cacheDriver)->get('__health_check__');
                // we don't care about value; just ensure call doesn't throw
            }
        } catch (\Exception $e) {
            $ok = false;
            Log::error('Healthcheck cache: '.$e->getMessage());
            $errors[] = 'cache';
        }

        return response()->json(['status' => $ok ? 'ok' : 'error', 'details' => $errors], $ok ? 200 : 503);
    }
}
