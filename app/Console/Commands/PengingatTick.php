<?php

namespace App\Console\Commands;

use App\Services\PengingatTickService;
use Illuminate\Console\Command;

class PengingatTick extends Command
{
    protected $signature = 'pengingat:tick';

    protected $description = 'Materialisasi & proses pengingat MO yang jatuh tempo (jalan tiap menit)';

    public function handle(): int
    {
        PengingatTickService::jalankan();
        $this->info('pengingat:tick selesai pada '.now()->format('Y-m-d H:i:s'));

        return self::SUCCESS;
    }
}
