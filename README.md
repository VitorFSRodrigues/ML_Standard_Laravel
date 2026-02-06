# ML Standard (Laravel)

Projeto Laravel focado nas telas de **ML Standard** e **ML Retreinos**.

## Telas

### ML Standard
- `/orcMLstd` - Orçamentos ML Standard
- `/StdELE` - STD Elétrica
- `/StdTUB` - STD Tubulação
- `/orcMLstd/evidencias-uso` - Evidências de Uso

### ML Retreinos
- `/aprovacao` - Aprovação
- `/modelos_ml` - Modelos ML
- `/varredura` - Varredura (lista e detalhe)
- `/dicionarios` - Dicionários
- `/treino-logs` - Logs de Treino

## Observações

- A tabela `orcamentistas` é obrigatória para o funcionamento das telas de ML Standard.
- O CRUD de orçamentistas foi removido; mantenha `Orcamentista` + `OrcamentistasSeeder`.

## Referências rápidas

- `routes/web.php`
- `config/adminlte.php`
- `app/Http/Controllers/OrcMLstdController.php`
- `app/Http/Controllers/EvidenciasUsoMlController.php`
- `app/Http/Controllers/StdELEController.php`
- `app/Http/Controllers/StdTUBController.php`
- `app/Http/Controllers/MlAprovacaoController.php`
- `app/Http/Controllers/ModelosMlController.php`
- `app/Http/Controllers/VarreduraController.php`
- `app/Http/Controllers/DicionariosController.php`
- `app/Http/Controllers/TreinoLogsController.php`
