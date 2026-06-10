# Faturas Municipais

Este repositório é uma versão reduzida e anonimizada de uma funcionalidade de
faturas que implementei em um sistema Laravel legado. O objetivo é mostrar o
trabalho técnico envolvido sem publicar o projeto corporativo, dados reais ou
detalhes internos da integração.

## Confidencialidade

O código foi adaptado especificamente para portfólio:

- dados de municípios, documentos, valores e linhas digitáveis são fictícios;
- credenciais, URLs internas, planilhas, relatórios e arquivos operacionais foram removidos;
- queries, views e identificadores internos do ERP não fazem parte deste repositório;
- regras comerciais sensíveis foram reduzidas a exemplos técnicos equivalentes;
- o modo padrão usa mocks e não tenta acessar nenhum ambiente externo;
- os PDFs são demonstrativos e não representam documentos bancários reais.

## O problema

A funcionalidade precisava reunir informações que chegavam de fontes diferentes:
contratos, parcelas, baixas e boletos do ERP/SAP. Esses registros nem sempre
tinham a mesma chave disponível, mas o frontend precisava receber uma visão
única da cobrança por município, com datas, valores, situação e documento
associado.

No legado, parte desse fluxo cresceu dentro do controller junto com consultas
SQL específicas do ambiente. Para o showcase, mantive o comportamento que
explica o trabalho realizado, mas retirei os detalhes que pertencem à empresa e
separei as responsabilidades em classes menores.

## O que eu implementei

Minha participação nessa frente envolveu:

- criação das rotas de consulta geral e por município;
- consulta e normalização de dados vindos do ERP/SAP;
- montagem da resposta consumida pelo frontend;
- relacionamento entre parcelas e boletos;
- cálculo da situação da parcela e do município;
- paginação e ordenação das parcelas mais recentes;
- endpoints para consulta de documentos mensais e anuais;
- sincronização da situação dos municípios por API e comando de console;
- tratamento de indisponibilidade da integração externa;
- remoção de campos internos antes da resposta pública.

A associação entre parcela e boleto tenta primeiro o identificador do documento.
Quando o ERP não fornece essa relação, o código usa vencimento e valor como
fallback controlado. Essa ordem evita ligar uma parcela ao boleto errado apenas
porque os valores coincidem.

## Decisões do showcase

Separei a comunicação HTTP no `SAPClient`, para o controller não conhecer login,
cookies, timeout ou detalhes do Service Layer. O `SAPService` escolhe entre o
provider mock e os endpoints configurados por ambiente. A montagem das faturas
ficou no `FaturaDigitalService`, enquanto o `FaturaDigitalResource` define o que
é exposto na API.

Mantive nomes próximos do legado onde isso ajuda a reconhecer a evolução do
código, mas removi consultas e constantes específicas da organização. O
parâmetro `cod_cnm` foi preservado somente na rota por compatibilidade com o
contrato original apresentado neste showcase; internamente ele é tratado como
um código genérico de município.

As respostas têm limite máximo de 100 itens por página. No modo mock isso é
suficiente para demonstrar o fluxo. Em uma integração real, filtros e paginação
também devem ser enviados ao ERP para evitar carregar coleções grandes na
memória.

## Tecnologias

- PHP 7.1.3+
- Laravel 5.7
- REST API
- Guzzle HTTP
- ERP/SAP Service Layer
- JSON Resources
- Services e console commands
- Cache para acompanhar a sincronização
- PHPUnit e Mockery

O projeto original também trabalhava com SQL Server e estruturas de consulta do
ERP. Esses objetos foram substituídos por um contrato HTTP genérico para não
publicar nomes de tabelas, views ou queries internas.

## Endpoints

| Método | Endpoint | Finalidade |
| --- | --- | --- |
| `GET` | `/api/list/faturas` | Lista faturas com filtros e paginação |
| `GET` | `/api/list/faturas/{cod_cnm}` | Detalha as parcelas de um município |
| `POST` | `/api/list/faturas/atualizar` | Recalcula a situação dos municípios |
| `GET` | `/api/list/faturas/atualizar/status/{id}` | Consulta o resultado da sincronização |
| `GET` | `/api/faturas/{codMun}/boletos/{vencimento}/pdf` | Gera um PDF demonstrativo mensal |
| `GET` | `/api/faturas/{codMun}/boletos/anual/pdf` | Gera um PDF demonstrativo anual |

Filtros disponíveis na listagem: `state`, `search`, `status`,
`payment_method`, `due_from`, `due_to`, `page` e `per_page`.

## Fluxo

1. A API recebe o código do município ou os filtros da listagem.
2. O service consulta municípios, parcelas e boletos no provider configurado.
3. Datas, valores, método de pagamento e status são normalizados.
4. Parcelas e boletos são relacionados por documento ou, como fallback, por vencimento e valor.
5. A situação do município é calculada a partir das parcelas relevantes.
6. O Resource remove campos internos e devolve uma resposta paginada.

## Estrutura relevante

```text
app/
├── Console/Commands/MunicipiosAtualizarSituacaoCommand.php
├── Enums/
│   ├── BoletoStatus.php
│   └── FaturaStatus.php
├── Exceptions/ERPIntegrationException.php
├── Http/
│   ├── Clients/SAPClient.php
│   ├── Controllers/FaturaDigitalController.php
│   └── Resources/FaturaDigitalResource.php
└── Services/
    ├── BillingPdfService.php
    ├── FaturaDigitalService.php
    ├── MunicipioSituacaoSyncService.php
    └── SAPService.php
config/
├── billing.php
└── services.php
docs/
├── api-examples.md
├── architecture.md
└── security-and-anonymization.md
routes/api.php
tests/
├── Feature/FaturaDigitalApiTest.php
└── Unit/FaturaDigitalServiceTest.php
```

## Como rodar

O Laravel 5.7 é uma restrição intencional deste recorte legado. Use PHP 7.2 a
7.4 para reproduzir o ambiente com menos incompatibilidades.

O Composer resolve as dependências como PHP 7.4.33 para impedir que bibliotecas
transitivas incompatíveis com PHP 7 entrem no lock.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

No PowerShell, use `Copy-Item .env.example .env`.

O `.env.example` já define `ERP_DRIVER=mock`. Portanto, não é necessário banco
de dados nem acesso ao SAP para consultar os endpoints. Para ligar um provider
real, altere o driver e configure somente variáveis de ambiente genéricas.

## Exemplo de resposta

```json
{
  "data": {
    "municipio": {
      "codigo": "1000001",
      "nome": "Municipality Alpha",
      "uf": "AA"
    },
    "situacao": "Em dia",
    "tipo_pagamento": "Boleto bancário",
    "valor_total": 2501,
    "parcelas": [
      {
        "referencia": "2026-06",
        "valor": 1250.5,
        "vencimento": "2026-06-25",
        "pagamento": null,
        "situacao": "Em aberto",
        "tipo_pagamento": "Boleto bancário",
        "boleto": {
          "document": "INV-DEMO-002",
          "due_date": "2026-06-25",
          "amount": 1250.5,
          "status": "Confirmado",
          "digital_line": "11111.11111 11111.111111 11111.111111 1 00000000125050",
          "pdf_available": true
        }
      }
    ],
    "paginacao": {
      "current_page": 1,
      "per_page": 12,
      "total": 2,
      "last_page": 1
    }
  }
}
```

Mais exemplos estão em [docs/api-examples.md](docs/api-examples.md).
