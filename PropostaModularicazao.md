# ProjCustoFase — Resumo de Modularizacao (Opcao B)

## Contexto
- Projeto sistematiza uma planilha orcamentaria complexa com milhares de vinculos.
- Objetivo: UI agradavel, BD estruturado e motor de calculos em Python com performance similar ao Excel.
- Laravel fica como front-end e orquestrador.

## Decisao
- Adotar monolito modular em Laravel com Opcao B (auto descoberta + allowlist).
- Cada dominio vira um modulo (PU, Dados, Fases, BDI, etc.).
- Modulos isolam rotas, views, migrations, services e testes.

## Estrutura sugerida
```text
app/
  Modules/
    MLStandard/
    MLRetreinamentos/
  Shared/
```

## Modulos iniciais
### ML Standard
- Escopo: telas e regras do fluxo padrao de ML.
- Rotas: prefixo `ml-standard` e namespace `mlstandard.*`.
- Views: namespace `mlstandard::`.
- Integracao com motor Python: endpoints de calculo do fluxo padrao.

### ML Retreinamentos
- Escopo: telas e regras do fluxo de retreinamento.
- Rotas: prefixo `ml-retreinamentos` e namespace `mlretreinamentos.*`.
- Views: namespace `mlretreinamentos::`.
- Integracao com motor Python: endpoints especificos de retreinamento.

## Como funciona a comunicacao
- O "main" nao fala com modulo por HTTP; tudo roda na mesma app.
- Cada modulo registra suas rotas via ServiceProvider.
- O `routes/web.php` do main fica apenas com rotas globais.

## Integracao com motor Python
- Motor de calculos separado (ex.: FastAPI).
- Contrato versionado (v1/v2) e idempotente.
- Sincrono para calculos pequenos; fila assincrona para calculos longos.
- Laravel persiste resultados; motor nao grava direto no mesmo BD.

## Boas praticas de entrega/merge
- PRs pequenos e frequentes.
- CHANGELOG curto por entrega (rotas, views, config, migrations).
- Apontar arquivos sensiveis a conflito.
- Feature flags quando a entrega nao pode ser ativada.

## Conclusao
- Com 15+ modulos previstos, Opcao B reduz manutencao manual e padroniza o crescimento.
- Recomendado seguir com monolito modular + motor Python externo.

## Proximos passos sugeridos
1. Criar `config/modules.php` com allowlist e enable/disable.
2. Criar `ModulesServiceProvider` para registrar providers dos modulos.
3. Estruturar `ML Standard` como primeiro modulo (rotas, views, migrations, services).
4. Estruturar `ML Retreinamentos` como segundo modulo (rotas, views, migrations, services).
