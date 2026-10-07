<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function clearCache(Request $request)
    {
        $ok = Cache::forget('inventario.dashboard');
        return response()->json(['cleared' => (bool) $ok]);
    }
}
