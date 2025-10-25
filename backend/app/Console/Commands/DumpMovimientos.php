<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DumpMovimientos extends Command
{
    protected $signature = 'dump:movimientos {limit=5}';
    protected $description = 'Dump recent movimientos_productos_finales';

    public function handle()
    {
        $limit = (int) $this->argument('limit');
        $movs = \App\Models\MovimientoProductoFinal::orderBy('id','desc')->take($limit)->get();
        foreach ($movs as $m) {
            $this->line(json_encode($m->toArray()));
        }
        return 0;
    }
}
