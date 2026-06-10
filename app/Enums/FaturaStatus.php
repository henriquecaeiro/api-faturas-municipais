<?php

namespace App\Enums;

class FaturaStatus
{
    const OPEN = 'Em aberto';
    const OVERDUE = 'Em atraso';
    const PAID = 'Pago';
    const CANCELED = 'Cancelada';
    const LEGAL_ACTION = 'Ação judicial';
    const NOT_CONTRIBUTOR = 'Não contribuinte';
}
