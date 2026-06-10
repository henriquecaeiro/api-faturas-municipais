<?php

namespace App\Services;

use App\Exceptions\ERPIntegrationException;
use App\Http\Clients\SAPClient;

class SAPService
{
    private $client;

    public function __construct(SAPClient $client)
    {
        $this->client = $client;
    }

    public function listMunicipalities()
    {
        if ($this->usesMockData()) {
            return array_values(config('billing.mock.municipalities', []));
        }

        return $this->fetchCollection(config('services.erp.endpoints.municipalities'));
    }

    public function findMunicipality($municipalityCode)
    {
        foreach ($this->listMunicipalities() as $municipality) {
            if ((string) $municipality['code'] === (string) $municipalityCode) {
                return $municipality;
            }
        }

        return null;
    }

    public function listInstallments($municipalityCode = null)
    {
        return $this->loadBillingCollection('installments', $municipalityCode);
    }

    public function listBankSlips($municipalityCode = null)
    {
        return $this->loadBillingCollection('bank_slips', $municipalityCode);
    }

    private function loadBillingCollection($collection, $municipalityCode)
    {
        if ($this->usesMockData()) {
            $items = array_values(config('billing.mock.' . $collection, []));
        } else {
            $items = $this->fetchCollection(
                config('services.erp.endpoints.' . $collection),
                $municipalityCode ? ['municipality_code' => $municipalityCode] : []
            );
        }

        if ($municipalityCode === null) {
            return $items;
        }

        return array_values(array_filter($items, function ($item) use ($municipalityCode) {
            return (string) ($item['municipality_code'] ?? '') === (string) $municipalityCode;
        }));
    }

    private function fetchCollection($endpoint, array $query = [])
    {
        if (empty($endpoint)) {
            throw new ERPIntegrationException('The ERP endpoint is not configured.');
        }

        $items = [];
        $next = $endpoint;
        $nextQuery = $query;

        do {
            $payload = $this->client->getJson($next, $nextQuery);
            $pageItems = $payload['data'] ?? ($payload['value'] ?? []);

            if (!is_array($pageItems)) {
                throw new ERPIntegrationException('The ERP collection response is invalid.');
            }

            $items = array_merge($items, $pageItems);
            $next = $payload['next'] ?? ($payload['odata.nextLink'] ?? null);
            $nextQuery = [];
        } while (!empty($next));

        return $items;
    }

    private function usesMockData()
    {
        return config('services.erp.driver', 'mock') === 'mock';
    }
}
