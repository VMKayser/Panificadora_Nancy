<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Mail\PedidoConfirmado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPedidoConfirmadoMail implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /** @var \App\Models\Pedido */
    protected $pedido;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Pedido  $pedido
     * @return void
     */
    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::to($this->pedido->cliente_email)->send(new PedidoConfirmado($this->pedido));
        } catch (\Exception $e) {
            // Log and allow queue retry behavior to handle transient failures
            report($e);
        }
    }
}
