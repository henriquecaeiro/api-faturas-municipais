<?php

namespace App\Services;

use App\Enums\FaturaStatus;
use Carbon\Carbon;

class FaturaDigitalService
{
    private $erp;

    public function __construct(SAPService $erp)
    {
        $this->erp = $erp;
    }

    public function listBilling(array $filters, $page, $perPage)
    {
        $municipalities = $this->erp->listMunicipalities();
        $installments = $this->erp->listInstallments();
        $bankSlips = $this->erp->listBankSlips();
        $records = [];

        foreach ($municipalities as $municipality) {
            if (!$this->matchesMunicipalityFilters($municipality, $filters)) {
                continue;
            }

            $record = $this->buildBillingRecord($municipality, $installments, $bankSlips, $filters);
            if (!$this->matchesBillingFilters($record, $filters)) {
                continue;
            }

            $records[] = $record;
        }

        usort($records, function ($left, $right) {
            return strcmp($left['municipality']['name'], $right['municipality']['name']);
        });

        $total = count($records);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($records, $offset, $perPage),
            'meta' => $this->paginationMeta($page, $perPage, $total),
        ];
    }

    public function getMunicipalityBilling($municipalityCode, $page, $perPage)
    {
        $municipality = $this->erp->findMunicipality($municipalityCode);
        if ($municipality === null) {
            return null;
        }

        $record = $this->buildBillingRecord(
            $municipality,
            $this->erp->listInstallments($municipalityCode),
            $this->erp->listBankSlips($municipalityCode),
            []
        );

        $total = count($record['installments']);
        $record['installments'] = array_slice(
            $record['installments'],
            ($page - 1) * $perPage,
            $perPage
        );
        $record['meta'] = $this->paginationMeta($page, $perPage, $total);

        return $record;
    }

    public function findBankSlip($municipalityCode, $dueDate)
    {
        $targetDate = $this->normalizeDate($dueDate);

        foreach ($this->erp->listBankSlips($municipalityCode) as $bankSlip) {
            if (!empty($bankSlip['annual'])) {
                continue;
            }

            if ($this->normalizeDate($bankSlip['due_date'] ?? null) === $targetDate) {
                return $bankSlip;
            }
        }

        return null;
    }

    public function findAnnualBankSlip($municipalityCode)
    {
        foreach ($this->erp->listBankSlips($municipalityCode) as $bankSlip) {
            if (!empty($bankSlip['annual'])) {
                return $bankSlip;
            }
        }

        return null;
    }

    public function calculateMunicipalityStatus($municipalityCode)
    {
        $record = $this->getMunicipalityBilling($municipalityCode, 1, 1000);

        return $record ? $record['status'] : null;
    }

    private function buildBillingRecord(
        array $municipality,
        array $allInstallments,
        array $allBankSlips,
        array $filters
    ) {
        $code = (string) $municipality['code'];
        $installments = array_values(array_filter($allInstallments, function ($installment) use ($code) {
            return (string) ($installment['municipality_code'] ?? '') === $code;
        }));
        $bankSlips = array_values(array_filter($allBankSlips, function ($bankSlip) use ($code) {
            return (string) ($bankSlip['municipality_code'] ?? '') === $code;
        }));

        $publicInstallments = [];
        foreach ($installments as $installment) {
            $normalized = $this->normalizeInstallment($installment, $bankSlips);
            if ($this->matchesInstallmentFilters($normalized, $filters)) {
                $publicInstallments[] = $normalized;
            }
        }

        usort($publicInstallments, function ($left, $right) {
            return strcmp($right['reference'], $left['reference']);
        });

        return [
            'municipality' => [
                'code' => $code,
                'name' => (string) $municipality['name'],
                'state' => (string) ($municipality['state'] ?? ''),
            ],
            'status' => $this->resolveMunicipalityStatus($municipality, $publicInstallments),
            'payment_method' => $this->normalizePaymentMethod($municipality['payment_method'] ?? null),
            'total_amount' => array_reduce($publicInstallments, function ($total, $installment) {
                return $total + (float) $installment['amount'];
            }, 0.0),
            'installments' => $publicInstallments,
        ];
    }

    private function normalizeInstallment(array $installment, array $bankSlips)
    {
        $dueDate = $this->normalizeDate($installment['due_date'] ?? null);
        $amount = round((float) ($installment['amount'] ?? 0), 2);
        $document = $this->nullableString($installment['document_id'] ?? null);
        $bankSlip = $this->matchBankSlip($document, $dueDate, $amount, $bankSlips);

        return [
            'reference' => $this->normalizeReference($installment['reference'] ?? null),
            'amount' => $amount,
            'due_date' => $dueDate,
            'payment_date' => $this->normalizeDate($installment['payment_date'] ?? null),
            'status' => $this->resolveInstallmentStatus($installment, $dueDate),
            'payment_method' => $this->normalizePaymentMethod($installment['payment_method'] ?? null),
            'bank_slip' => $bankSlip ? $this->normalizeBankSlip($bankSlip) : null,
        ];
    }

    private function matchBankSlip($document, $dueDate, $amount, array $bankSlips)
    {
        if ($document !== null) {
            foreach ($bankSlips as $bankSlip) {
                if (!empty($bankSlip['annual'])) {
                    continue;
                }

                if ($this->nullableString($bankSlip['document_id'] ?? null) === $document) {
                    return $bankSlip;
                }
            }
        }

        // Some legacy ERP records do not carry the document relation. Due date and
        // amount are used only as a controlled fallback after document matching.
        foreach ($bankSlips as $bankSlip) {
            if (!empty($bankSlip['annual'])) {
                continue;
            }

            $sameDate = $this->normalizeDate($bankSlip['due_date'] ?? null) === $dueDate;
            $sameAmount = abs((float) ($bankSlip['amount'] ?? 0) - $amount) < 0.01;

            if ($sameDate && $sameAmount) {
                return $bankSlip;
            }
        }

        return null;
    }

    private function normalizeBankSlip(array $bankSlip)
    {
        return [
            'document' => $this->nullableString($bankSlip['document_id'] ?? null),
            'due_date' => $this->normalizeDate($bankSlip['due_date'] ?? null),
            'amount' => round((float) ($bankSlip['amount'] ?? 0), 2),
            'status' => (string) ($bankSlip['status'] ?? 'Gerado'),
            'digital_line' => $this->nullableString($bankSlip['digital_line'] ?? null),
            'pdf_available' => true,
        ];
    }

    private function resolveInstallmentStatus(array $installment, $dueDate)
    {
        if (empty($installment['contributor'])) {
            return FaturaStatus::NOT_CONTRIBUTOR;
        }

        if (!empty($installment['legal_action'])) {
            return FaturaStatus::LEGAL_ACTION;
        }

        if (!empty($installment['canceled'])) {
            return FaturaStatus::CANCELED;
        }

        if (!empty($installment['payment_date'])) {
            return FaturaStatus::PAID;
        }

        if ($dueDate !== null && Carbon::createFromFormat('Y-m-d', $dueDate)->lt(Carbon::today())) {
            return FaturaStatus::OVERDUE;
        }

        return FaturaStatus::OPEN;
    }

    private function resolveMunicipalityStatus(array $municipality, array $installments)
    {
        if (array_key_exists('contributor', $municipality) && empty($municipality['contributor'])) {
            return FaturaStatus::NOT_CONTRIBUTOR;
        }

        foreach ($installments as $installment) {
            if ($installment['status'] === FaturaStatus::LEGAL_ACTION) {
                return FaturaStatus::LEGAL_ACTION;
            }
        }

        foreach ($installments as $installment) {
            if ($installment['status'] === FaturaStatus::OVERDUE) {
                return 'Inadimplente';
            }
        }

        return 'Em dia';
    }

    private function matchesMunicipalityFilters(array $municipality, array $filters)
    {
        if (!empty($filters['state'])
            && strtoupper((string) ($municipality['state'] ?? '')) !== strtoupper($filters['state'])) {
            return false;
        }

        if (!empty($filters['search'])) {
            $haystack = $this->normalizeText(
                ($municipality['name'] ?? '') . ' ' . ($municipality['code'] ?? '')
            );
            if (strpos($haystack, $this->normalizeText($filters['search'])) === false) {
                return false;
            }
        }

        return true;
    }

    private function matchesBillingFilters(array $record, array $filters)
    {
        if (!empty($filters['status'])
            && $this->normalizeText($record['status']) !== $this->normalizeText($filters['status'])) {
            return false;
        }

        if (!empty($filters['payment_method'])
            && $this->normalizeText($record['payment_method']) !== $this->normalizeText($filters['payment_method'])) {
            return false;
        }

        return !empty($record['installments']) || empty($filters['only_with_installments']);
    }

    private function matchesInstallmentFilters(array $installment, array $filters)
    {
        if (!empty($filters['due_from'])
            && ($installment['due_date'] === null || $installment['due_date'] < $filters['due_from'])) {
            return false;
        }

        if (!empty($filters['due_to'])
            && ($installment['due_date'] === null || $installment['due_date'] > $filters['due_to'])) {
            return false;
        }

        return true;
    }

    private function normalizePaymentMethod($value)
    {
        $normalized = $this->normalizeText((string) $value);

        if (strpos($normalized, 'DEBIT') !== false) {
            return 'Débito em conta';
        }
        if (strpos($normalized, 'TRANSFER') !== false) {
            return 'Transferência bancária';
        }
        if (strpos($normalized, 'BOLETO') !== false || strpos($normalized, 'BANK SLIP') !== false) {
            return 'Boleto bancário';
        }

        return $value ? (string) $value : 'Não informado';
    }

    private function normalizeReference($value)
    {
        $date = $this->normalizeDate($value);
        return $date ? substr($date, 0, 7) : (string) $value;
    }

    private function normalizeDate($value)
    {
        if (empty($value)) {
            return null;
        }

        foreach (['Y-m-d', 'Ymd', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $value)->format('Y-m-d');
            } catch (\Exception $exception) {
                //
            }
        }

        return null;
    }

    private function nullableString($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizeText($value)
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim((string) $value));
        return strtoupper($ascii === false ? (string) $value : $ascii);
    }

    private function paginationMeta($page, $perPage, $total)
    {
        return [
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => (int) $total,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
