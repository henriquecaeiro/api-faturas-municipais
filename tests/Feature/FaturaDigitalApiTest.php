<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaturaDigitalApiTest extends TestCase
{
    public function testListsMockMunicipalityBillingData()
    {
        $response = $this->getJson('/api/list/faturas/1000001');

        $response->assertStatus(200)
            ->assertJsonFragment(['nome' => 'Municipality Alpha'])
            ->assertJsonStructure([
                'data' => [
                    'municipio',
                    'situacao',
                    'tipo_pagamento',
                    'valor_total',
                    'parcelas',
                    'paginacao',
                ],
            ]);
    }

    public function testRejectsInvalidPagination()
    {
        $this->getJson('/api/list/faturas?per_page=500')
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function testSynchronizesAndExposesStatus()
    {
        $start = $this->postJson('/api/list/faturas/atualizar', ['limit' => 2]);

        $start->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'completed',
                'processed' => 2,
                'updated_count' => 2,
            ]);

        $syncId = $start->json('data.sync_id');

        $this->getJson('/api/list/faturas/atualizar/status/' . $syncId)
            ->assertStatus(200)
            ->assertJsonFragment([
                'sync_id' => $syncId,
                'status' => 'completed',
            ]);
    }

    public function testGeneratesMockPdfWithoutExternalBankData()
    {
        $response = $this->get('/api/faturas/1000001/boletos/2026-06-25/pdf');

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
    }
}
