<?php

namespace App\Http\Controllers;

use App\Exceptions\ERPIntegrationException;
use App\Http\Resources\FaturaDigitalResource;
use App\Services\BillingPdfService;
use App\Services\FaturaDigitalService;
use App\Services\MunicipioSituacaoSyncService;
use App\Services\SAPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FaturaDigitalController extends Controller
{
    private $billing;
    private $erp;
    private $pdf;

    public function __construct(
        FaturaDigitalService $billing,
        SAPService $erp,
        BillingPdfService $pdf
    ) {
        $this->billing = $billing;
        $this->erp = $erp;
        $this->pdf = $pdf;
    }

    public function showAll(Request $request)
    {
        $validation = $this->validateListRequest($request);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(100, max(1, (int) $request->get('per_page', 20)));
            $result = $this->billing->listBilling($request->only([
                'state',
                'search',
                'status',
                'payment_method',
                'due_from',
                'due_to',
                'only_with_installments',
            ]), $page, $perPage);

            $result['data'] = array_map(function ($record) use ($request) {
                return (new FaturaDigitalResource($record))->resolve($request);
            }, $result['data']);

            return response()->json($result);
        } catch (ERPIntegrationException $exception) {
            return $this->integrationError($exception);
        }
    }

    public function show(Request $request, $codCnm)
    {
        $validator = Validator::make(
            array_merge($request->all(), ['cod_cnm' => $codCnm]),
            [
                'cod_cnm' => ['required', 'regex:/^[A-Za-z0-9_-]{1,20}$/'],
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid request parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $record = $this->billing->getMunicipalityBilling(
                $codCnm,
                max(1, (int) $request->get('page', 1)),
                min(100, max(1, (int) $request->get('per_page', 12)))
            );

            if ($record === null) {
                return response()->json(['message' => 'Municipality not found.'], 404);
            }

            return response()->json([
                'data' => (new FaturaDigitalResource($record))->resolve($request),
            ]);
        } catch (ERPIntegrationException $exception) {
            return $this->integrationError($exception);
        }
    }

    public function startMunicipiosSituacaoSync(
        Request $request,
        MunicipioSituacaoSyncService $syncService
    ) {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid request parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return response()->json([
                'data' => $syncService->start($request->get('limit')),
            ]);
        } catch (ERPIntegrationException $exception) {
            return $this->integrationError($exception);
        }
    }

    public function getMunicipiosSituacaoSyncStatus($syncId, MunicipioSituacaoSyncService $syncService)
    {
        if (!preg_match('/^[A-Za-z0-9-]{1,64}$/', $syncId)) {
            return response()->json(['message' => 'Invalid synchronization identifier.'], 422);
        }

        $status = $syncService->status($syncId);

        return $status === null
            ? response()->json(['message' => 'Synchronization not found.'], 404)
            : response()->json(['data' => $status]);
    }

    public function imprimirBoleto($codMun, $vencimento)
    {
        try {
            $municipality = $this->erp->findMunicipality($codMun);
            $bankSlip = $this->billing->findBankSlip($codMun, $vencimento);

            if ($municipality === null || $bankSlip === null) {
                return response()->json(['message' => 'Billing document not found.'], 404);
            }

            return response($this->pdf->renderBankSlip($municipality, $bankSlip, $vencimento), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="billing-document.pdf"');
        } catch (ERPIntegrationException $exception) {
            return $this->integrationError($exception);
        }
    }

    public function imprimirBoletoAnual($codMun)
    {
        try {
            $municipality = $this->erp->findMunicipality($codMun);
            $bankSlip = $this->billing->findAnnualBankSlip($codMun);

            if ($municipality === null || $bankSlip === null) {
                return response()->json(['message' => 'Annual billing document not found.'], 404);
            }

            return response($this->pdf->renderBankSlip($municipality, $bankSlip, 'Annual'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="annual-billing-document.pdf"');
        } catch (ERPIntegrationException $exception) {
            return $this->integrationError($exception);
        }
    }

    private function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'state' => 'nullable|string|max:2',
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:40',
            'payment_method' => 'nullable|string|max:40',
            'due_from' => 'nullable|date_format:Y-m-d',
            'due_to' => 'nullable|date_format:Y-m-d|after_or_equal:due_from',
            'only_with_installments' => 'nullable|boolean',
        ]);

        if (!$validator->fails()) {
            return null;
        }

        return response()->json([
            'message' => 'Invalid request parameters.',
            'errors' => $validator->errors(),
        ], 422);
    }

    private function integrationError(ERPIntegrationException $exception)
    {
        Log::warning('ERP billing integration failed.', [
            'exception' => get_class($exception),
        ]);

        return response()->json([
            'message' => 'The billing provider is temporarily unavailable.',
        ], 502);
    }
}
