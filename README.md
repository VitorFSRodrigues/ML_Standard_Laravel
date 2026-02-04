# Sigob-Orc (Laravel)

Projeto Laravel para integrar Pipedrive ao fluxo de triagem, requisitos e orcamentos.

## 1. Pipedrive x Sigob-Orc (Laravel)

### 1.1 Webhook: como funciona e como esta aplicado

- Endpoint `POST /webhooks/pipedrive` recebe o evento do Pipedrive e registra log.
- `PipedriveWebhookController` identifica a entidade (deal ou organization) e enfileira jobs.
- `ImportDealFromPipedrive` importa deals, mapeia campos e cria/atualiza `Triagem` e `Requisito`.
- `UpdateOrganizationFromPipedrive` atualiza `Cliente` a partir da organizacao.
- `VerifyCsrfToken` libera a rota de webhook para integracao externa.

### 1.2 API-REST (a cada hora): check se webhook "esqueceu" de alguem

- `pipedrive:sync` roda no scheduler (`app/Console/Kernel.php`) a cada `interval_minutes` (padrao 60).
- O comando consulta deals atualizados com janela de `lookback_hours` e reprocessa o que mudou.
- Cada deal chama `ImportDealFromPipedrive` na fila.
- Tambem pode ser disparado manualmente pelo botao "Sincronizar com Pipedrive" na triagem ou via `POST /triagem/sync`.

### 1.3 Sem custo adicional

- Usa webhooks e REST API nativos do Pipedrive (sem middleware pago).
- O processamento assincrono e o scheduler sao recursos do proprio Laravel.

## 2. Dentro de Sigob-Orc

### 2.1 Triagem

- Botao de sincronizar: `resources/views/components/triagem/header-actions.blade.php` chama `syncNow`.
- Edicao final antes de virar orcamento: botao "Editar" abre `modal.triagem.create-edit` e preenche campos obrigatorios.
- Calculo de % de probabilidade: tela de triagem mostra `modal.triagem.resumo` com score do `TriagemScoring`.
- Movimentacao Triagem x Acervo x Orcamentistas: botao "Mover" usa `modal.triagem.move` e grava em `triagem_movimentos`.
- Declinio de orcamento: botao "Declinar" desativa e envia para acervo via `modal.triagem.confirm-decline`.

### 2.2 Requisitos

- Rota da tela: `/requisitos` (em `routes/web.php` e menu em `config/adminlte.php`).
- Se existir link legado `/orcamentos`, a troca correta e para `/requisitos`.
- Item pre-criado via `TriagemObserver` (1:1 com `Requisito`).
- Botao de edicao final: `RequisitoTable` abre `modal.requisitos.edit` com dados da triagem e campos de requisitos.

### 2.3 Orcamentistas

- Lista do time com pontuacao por volume de orcamentos: `OrcamentistaTable` calcula `orcamentos_count`.
- Tela individual mostra itens atribuidos (`OrcamentistaIdTable`) com mover, declinar e iniciar fases.

### 2.4 Acervo Tecnico

- `AcervoTable` lista itens em acervo com botoes de mover, reativar e declinar.
- Reativacao usa `modal.acervo.confirm-reactivate` e declinio reutiliza o modal de triagem.

## Referencias rapidas

- `routes/web.php`
- `app/Http/Controllers/PipedriveWebhookController.php`
- `app/Services/PipedriveService.php`
- `app/Console/Commands/SyncPipedriveDeals.php`
- `app/Jobs/ImportDealFromPipedrive.php`
- `app/Jobs/RunPipedriveSync.php`
- `app/Livewire/Powergrid/TriagemTable.php`
- `app/Livewire/Modal/Triagem/CreateEdit.php`
- `app/Livewire/Modal/Triagem/Resumo.php`
- `app/Livewire/Powergrid/RequisitoTable.php`
- `app/Livewire/Modal/Requisitos/Edit.php`
- `app/Observers/TriagemObserver.php`
- `app/Livewire/Powergrid/OrcamentistaTable.php`
- `app/Livewire/Powergrid/AcervoTable.php`
