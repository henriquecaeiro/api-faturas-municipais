# Security and Anonymization

## Removed from the source copy

- the original contacts application and unrelated controllers;
- corporate seeders, users, e-mail addresses and password hashes;
- ERP SQL, view names, query identifiers and classification IDs;
- bank, branch and account descriptions;
- the external corporate billing proxy and its URL;
- real spreadsheets and spreadsheet templates;
- benchmark CSV output;
- `public/info.php`;
- `.rnd`;
- `composer.phar`;
- old README files;
- frontend assets, logos and organization names;
- generated API documentation from the internal project.

Explicitly removed files included:

- `public/contatos CNM PF e PJ.xlsx`
- `public/modelo.pessoas.xlsx`
- `public/modelo.pessoas - Copia.xlsx`
- `public/info.php`
- `tmp_faturas_benchmark.csv`
- `.rnd`
- `composer.phar`
- `readme.md.old`

## Replaced or simplified

- real municipality names became `Municipality Alpha`, `Beta` and `Gamma`;
- real codes and document numbers became fictional demo identifiers;
- proprietary ERP queries became configurable generic HTTP endpoints;
- banking metadata became the generic concept `Bank slip`;
- the real document renderer became a small mock PDF generator;
- database persistence during synchronization became cache-backed demo state;
- sensitive business classifications became simple contributor flags.

## Manual review before GitHub

1. Run `rg -n -i "company-name|internal-domain|real-client-name" .`.
2. Run `rg --files -g ".env*" -g "*.xlsx" -g "*.csv" -g "*.sql" -g "*.dump"`.
3. Confirm that only `.env.example` exists and every credential value is empty.
4. Inspect `git status` after initializing the new repository.
5. Review the first commit instead of importing the corporate Git history.
6. Check `storage/logs`, IDE folders and operating-system metadata.
7. Run a secret scanner such as Gitleaks before making the repository public.
8. Open every mock file and confirm that names, codes, amounts and documents are fictional.
9. Run `composer audit --locked --no-dev` and review the known Laravel 5.7 advisories.

## Remaining point of attention

The public route still uses the parameter name `cod_cnm` to demonstrate
compatibility with the legacy API contract requested for this showcase. It does
not contain a real code and is mapped internally to a generic municipality
identifier. Rename it before publishing if even the acronym is considered
confidential by the organization.

Laravel 5.7 is out of support. On June 10, 2026, the locked production
dependencies reported 14 advisories across 5 packages. This is a known legacy
risk and the showcase must not be deployed publicly without a framework
upgrade.
