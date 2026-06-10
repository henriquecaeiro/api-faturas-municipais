<?php

namespace Tests\Unit;

use App\Services\FaturaDigitalService;
use App\Services\SAPService;
use Mockery;
use PHPUnit\Framework\TestCase;

class FaturaDigitalServiceTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testDocumentMatchHasPriorityOverDueDateAndAmountFallback()
    {
        $service = $this->makeService([
            $this->installment('DOC-2'),
        ], [
            $this->bankSlip('DOC-1'),
            $this->bankSlip('DOC-2'),
        ]);

        $result = $service->getMunicipalityBilling('1000001', 1, 12);

        $this->assertSame('DOC-2', $result['installments'][0]['bank_slip']['document']);
    }

    public function testDueDateAndAmountAreUsedWhenDocumentIsMissing()
    {
        $service = $this->makeService([
            $this->installment(null),
        ], [
            $this->bankSlip('DOC-FALLBACK'),
        ]);

        $result = $service->getMunicipalityBilling('1000001', 1, 12);

        $this->assertSame('DOC-FALLBACK', $result['installments'][0]['bank_slip']['document']);
    }

    public function testPastDueOpenInstallmentMarksMunicipalityAsOverdue()
    {
        $installment = $this->installment('DOC-1');
        $installment['due_date'] = '2020-01-10';

        $service = $this->makeService([$installment], []);
        $result = $service->getMunicipalityBilling('1000001', 1, 12);

        $this->assertSame('Em atraso', $result['installments'][0]['status']);
        $this->assertSame('Inadimplente', $result['status']);
    }

    private function makeService(array $installments, array $bankSlips)
    {
        $erp = Mockery::mock(SAPService::class);
        $erp->shouldReceive('findMunicipality')->andReturn([
            'code' => '1000001',
            'name' => 'Municipality Test',
            'state' => 'TT',
            'contributor' => true,
            'payment_method' => 'Bank slip',
        ]);
        $erp->shouldReceive('listInstallments')->andReturn($installments);
        $erp->shouldReceive('listBankSlips')->andReturn($bankSlips);

        return new FaturaDigitalService($erp);
    }

    private function installment($document)
    {
        return [
            'municipality_code' => '1000001',
            'document_id' => $document,
            'reference' => '2026-06-01',
            'due_date' => '2026-06-25',
            'payment_date' => null,
            'amount' => 100.00,
            'contributor' => true,
            'legal_action' => false,
            'canceled' => false,
            'payment_method' => 'Bank slip',
        ];
    }

    private function bankSlip($document)
    {
        return [
            'municipality_code' => '1000001',
            'document_id' => $document,
            'due_date' => '2026-06-25',
            'amount' => 100.00,
            'status' => 'Confirmado',
            'digital_line' => '00000.00000 00000.000000 00000.000000 0 00000000010000',
            'annual' => false,
        ];
    }
}
