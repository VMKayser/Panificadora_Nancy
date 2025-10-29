<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $phone;
    public string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $service = new WhatsAppService();
            $resp = $service->sendMessage($this->phone, $this->message);
            Log::info('SendWhatsAppMessage handled', ['phone' => $this->phone, 'resp' => $resp]);
        } catch (\Exception $e) {
            Log::error('Error en SendWhatsAppMessage: ' . $e->getMessage());
            // El trabajo puede reintentarse según configuración de la cola
            throw $e;
        }
    }
}
