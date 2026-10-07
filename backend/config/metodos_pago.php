<?php

return [
    'defaults' => [
        [
            'id' => 'qr-default',
            'codigo' => 'qr',
            'nombre' => 'Pago QR oficial',
            'descripcion' => 'Escanea el código QR corporativo y comparte el comprobante por WhatsApp.',
            'orden' => 10,
            'esta_activo' => true,
            'icono' => null,
            'comision_porcentaje' => 0,
        ],
        [
            'id' => 'cash-default',
            'codigo' => 'efectivo',
            'nombre' => 'Pago en efectivo',
            'descripcion' => 'Cancelación presencial al momento de la entrega o en caja.',
            'orden' => 20,
            'esta_activo' => true,
            'icono' => null,
            'comision_porcentaje' => 0,
        ],
    ],
];
