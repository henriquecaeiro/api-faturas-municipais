<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MunicipioSituacaoSyncService
{
    private $erp;
    private $billing;

    public function __construct(SAPService $erp, FaturaDigitalService $billing)
    {
        $this->erp = $erp;
        $this->billing = $billing;
    }

    public function start($limit = null)
    {
        return $this->run((string) Str::uuid(), $limit);
    }

    public function run($syncId = null, $limit = null, callable $progress = null)
    {
        $syncId = $syncId ?: (string) Str::uuid();
        $municipalities = $this->erp->listMunicipalities();

        if ($limit !== null) {
            $municipalities = array_slice($municipalities, 0, (int) $limit);
        }

        $status = [
            'sync_id' => $syncId,
            'status' => 'running',
            'processed' => 0,
            'total' => count($municipalities),
            'updated_count' => 0,
            'error_count' => 0,
            'current' => null,
        ];
        $this->storeStatus($status);

        foreach ($municipalities as $municipality) {
            $code = (string) $municipality['code'];

            try {
                $calculatedStatus = $this->billing->calculateMunicipalityStatus($code);
                Cache::put(
                    'billing.municipality_status.' . $code,
                    $calculatedStatus,
                    config('billing.sync_cache_minutes', 1440)
                );
                $status['updated_count']++;
                $status['current'] = [
                    'codigo' => $code,
                    'nome' => $municipality['name'],
                    'situacao' => $calculatedStatus,
                ];
            } catch (\Throwable $exception) {
                $status['error_count']++;
                $status['current'] = [
                    'codigo' => $code,
                    'nome' => $municipality['name'],
                    'situacao' => null,
                ];
            }

            $status['processed']++;
            $status['progress_percent'] = $status['total'] > 0
                ? (int) floor(($status['processed'] / $status['total']) * 100)
                : 100;
            $this->storeStatus($status);

            if ($progress) {
                $progress($status);
            }
        }

        $status['status'] = $status['error_count'] > 0 ? 'completed_with_errors' : 'completed';
        $status['progress_percent'] = 100;
        $status['finished_at'] = date(DATE_ATOM);
        $this->storeStatus($status);

        return $status;
    }

    public function status($syncId)
    {
        return Cache::get($this->statusKey($syncId));
    }

    private function storeStatus(array $status)
    {
        Cache::put(
            $this->statusKey($status['sync_id']),
            $status,
            config('billing.sync_cache_minutes', 1440)
        );
    }

    private function statusKey($syncId)
    {
        return 'billing.sync.' . $syncId;
    }
}
