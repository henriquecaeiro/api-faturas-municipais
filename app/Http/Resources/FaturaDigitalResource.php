<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaturaDigitalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'municipio' => [
                'codigo' => $this['municipality']['code'],
                'nome' => $this['municipality']['name'],
                'uf' => $this['municipality']['state'],
            ],
            'situacao' => $this['status'],
            'tipo_pagamento' => $this['payment_method'],
            'valor_total' => round((float) $this['total_amount'], 2),
            'parcelas' => array_map(function ($installment) {
                return [
                    'referencia' => $installment['reference'],
                    'valor' => round((float) $installment['amount'], 2),
                    'vencimento' => $installment['due_date'],
                    'pagamento' => $installment['payment_date'],
                    'situacao' => $installment['status'],
                    'tipo_pagamento' => $installment['payment_method'],
                    'boleto' => $installment['bank_slip'],
                ];
            }, $this['installments']),
            'paginacao' => $this['meta'] ?? null,
        ];
    }
}
