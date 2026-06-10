# API Examples

All identifiers and values below are fictional.

## List billing records

```http
GET /api/list/faturas?state=AA&page=1&per_page=20
```

```json
{
  "data": [
    {
      "municipio": {
        "codigo": "1000001",
        "nome": "Municipality Alpha",
        "uf": "AA"
      },
      "situacao": "Em dia",
      "tipo_pagamento": "Boleto bancário",
      "valor_total": 2501,
      "parcelas": [],
      "paginacao": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

## Start status synchronization

```http
POST /api/list/faturas/atualizar
Content-Type: application/json

{
  "limit": 2
}
```

```json
{
  "data": {
    "sync_id": "7cf27c55-0c45-4ef7-b34b-e92551be4ec2",
    "status": "completed",
    "processed": 2,
    "total": 2,
    "updated_count": 2,
    "error_count": 0,
    "progress_percent": 100
  }
}
```

## Validation error

```http
GET /api/list/faturas?per_page=500
```

```json
{
  "message": "Invalid request parameters.",
  "errors": {
    "per_page": [
      "The per page may not be greater than 100."
    ]
  }
}
```

## Provider unavailable

```json
{
  "message": "The billing provider is temporarily unavailable."
}
```
