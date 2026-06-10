# Architecture

## Component responsibilities

`FaturaDigitalController` validates HTTP input, selects response codes and
converts integration failures into a stable `502` response. It does not build
ERP paths or calculate billing status.

`SAPClient` contains the HTTP concerns of the ERP/SAP Service Layer: base URL,
timeout, TLS verification, cookie handling and optional login.

`SAPService` is the provider boundary. With `ERP_DRIVER=mock`, it reads the
fictional dataset from `config/billing.php`. With another driver, it requests
the generic endpoints declared in `config/services.php` and handles paginated
collections.

`FaturaDigitalService` joins municipalities, installments and bank slips. It
normalizes data, applies filters, orders records and calculates status.

`FaturaDigitalResource` is the public contract. ERP-only fields are never
forwarded directly.

`MunicipioSituacaoSyncService` recalculates municipality status and stores
progress in Laravel Cache. The synchronous implementation is deliberate for a
small showcase dataset; a production-sized run should use queued jobs.

## Installment and bank slip matching

The matching order is:

1. exact document identifier;
2. due date plus amount;
3. no bank slip relation.

The second rule is a fallback for incomplete legacy records. It is not applied
before document matching because multiple installments may share an amount.

## Failure handling

The client raises `ERPIntegrationException` without returning remote response
bodies to API consumers. The controller logs only the exception class and
returns a generic message. This avoids exposing provider URLs, credentials or
internal error payloads.
