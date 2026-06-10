<?php

namespace App\Console\Commands;

use App\Services\MunicipioSituacaoSyncService;
use Illuminate\Console\Command;

class MunicipiosAtualizarSituacaoCommand extends Command
{
    protected $signature = 'billing:sync-municipality-status {--limit=}';
    protected $description = 'Recalculates municipality billing status from the configured ERP provider.';

    public function handle(MunicipioSituacaoSyncService $syncService)
    {
        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('The --limit option must be a positive integer.');
            return 1;
        }

        $status = $syncService->run(null, $limit === null ? null : (int) $limit, function ($progress) {
            $this->line(sprintf(
                '%d/%d - %s',
                $progress['processed'],
                $progress['total'],
                $progress['current']['nome'] ?? 'Processing'
            ));
        });

        $this->info('Synchronization status: ' . $status['status']);
        return $status['error_count'] > 0 ? 1 : 0;
    }
}
