<?php

use Illuminate\Support\Facades\Route;

Route::prefix('list/faturas')->group(function () {
    Route::post('atualizar', 'FaturaDigitalController@startMunicipiosSituacaoSync');
    Route::get('atualizar/status/{id}', 'FaturaDigitalController@getMunicipiosSituacaoSyncStatus');
    Route::get('/', 'FaturaDigitalController@showAll');
    Route::get('{cod_cnm}', 'FaturaDigitalController@show');
});

Route::prefix('faturas/{codMun}/boletos')->group(function () {
    Route::get('anual/pdf', 'FaturaDigitalController@imprimirBoletoAnual');
    Route::get('{vencimento}/pdf', 'FaturaDigitalController@imprimirBoleto');
});
