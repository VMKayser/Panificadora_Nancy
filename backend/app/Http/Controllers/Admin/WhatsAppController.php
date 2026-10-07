<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsAppMessage;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /** List recent whatsapp messages (paginated) */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 30);
        $perPage = $perPage > 0 ? min($perPage, 200) : 30;

        $query = WhatsAppMessage::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate($perPage);
        return response()->json($messages);
    }

    /** Retry a failed message by id */
    public function retry($id)
    {
        $msg = WhatsAppMessage::find($id);
        if (!$msg) return response()->json(['message' => 'Not found'], 404);

        if ($msg->status === 'sent') {
            return response()->json(['message' => 'Already sent'], 400);
        }

        try {
            $msg->update(['status' => 'retrying']);
            SendWhatsAppMessage::dispatch($msg->to_phone, $msg->message);
            return response()->json(['message' => 'Retry dispatched']);
        } catch (\Exception $e) {
            Log::error('Error retrying whatsapp message: ' . $e->getMessage());
            $msg->update(['status' => 'failed']);
            return response()->json(['message' => 'Error dispatching retry'], 500);
        }
    }
}
