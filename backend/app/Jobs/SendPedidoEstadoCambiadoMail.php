<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Mail\PedidoEstadoCambiado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPedidoEstadoCambiadoMail implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /** @var \App\Models\Pedido */
    protected $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido;
    }

    public function handle()
    {
        try {
            Mail::to($this->pedido->cliente_email)->send(new PedidoEstadoCambiado($this->pedido));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
