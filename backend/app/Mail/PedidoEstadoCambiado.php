<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoEstadoCambiado extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Pedido $pedido)
    {
        // El pedido se pasa automáticamente a la vista
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $estadoTexto = match($this->pedido->estado) {
            'pendiente' => '⏳ Pendiente',
            'confirmado' => '✅ Confirmado',
            'preparando' => '👨‍🍳 Preparando',
            'listo' => '✨ Listo',
            'en_camino' => '🚗 En Camino',
            'entregado' => '📦 Entregado',
            'cancelado' => '❌ Cancelado',
            default => ucfirst($this->pedido->estado)
        };

        return new Envelope(
            subject: $estadoTexto . ' - Pedido #' . str_pad($this->pedido->id, 6, '0', STR_PAD_LEFT) . ' - Panificadora Nancy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pedido-estado-cambiado',
            with: [
                'pedido' => $this->pedido
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
