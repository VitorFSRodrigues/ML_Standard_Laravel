<?php
// config/pipedrive.php

return [

    /*
    |--------------------------------------------------------------------------
    | Credenciais / Base URL
    |--------------------------------------------------------------------------
    */
    'base_url'   => env('PIPEDRIVE_BASE_URL', 'https://api.pipedrive.com/v1'),
    'api_token'  => env('PIPEDRIVE_API_TOKEN'),
    'company'    => env('PIPEDRIVE_COMPANY_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Mapeamentos de campos (Deals -> Triagem)
    |--------------------------------------------------------------------------
    |
    | Observação:
    | - numero_orcamento: teremos várias origens possíveis. Usaremos a primeira
    |   que estiver preenchida (na ordem abaixo).
    |
    */

    'deal_fields' => [

        // numero_orcamento: primeira chave não-vazia na ordem abaixo
        'numero_orcamento_prioridades' => [
            '9defc0927da2bc7149e87b17d8523b4ab9cd42cb', // Número de orçamento Engenharia
            'eb6739bfedd3711afeb82683f3f3025459bf8500', // Número do Orçamento MCM
            '07f020f7f63882a1129a08179edd6b7bf66fa63d', // Número do Orçamento Metal
            '0d4fe4c52379243de4ecd86d215d80c81ca86275', // Número de Orçamento Fast
            'af8eeedfcf899c09871343b07178411e95583c47', // Número do Orçamento PAINEIS
        ],

        // Demais campos
        'cliente_id'         => 'org_id', // Organização (cliente) - system field
        'descricao_servico'  => 'title',  // Título do deal
        'cliente_final_id'   => '746c28f9f7cc152d9bbd3c28a5bb5044ad79a1a0', // Cliente Final (custom)
        'cidade_obra'        => '6dce728151bd72806794faa94d72889edfc09200', // (futuro) Cidade da Obra
        'estado_obra'        => 'e003d3debc1bf3d07d03d13ccbe0c226b321d123', // (futuro) Estado da obra
        'pais_obra'          => '9074b3541d30f724601edf25855dc86fb343547e', // (futuro) País
        'regime_contrato'    => '4ac638028a63dd152bb009638cf7b325678ba374', // Regime de contrato (custom)
    ],
    /*
    |--------------------------------------------------------------------------
    | Mapeamentos de campos (Organizations -> Clientes)
    |--------------------------------------------------------------------------
    */
    'organization_fields' => [
        'id'                => 'id',     // system field
        'nome_cliente'      => 'name',   // Nome
        'endereco_completo' => 'address', // Endereço (conforme fornecido)
        'municipio'         => 'address_admin_area_level_2', // Região de Endereço
        'estado'            => 'address_admin_area_level_1', // Estado de Endereço
        'pais'              => 'address_country',            // País de Endereço
        'cnpj'              => '22b35bc3a67ec3f162b54af7fb57542f10963d8c', // CNPJ (custom)
    ],

    'regime_contrato_options' => [
        // id_da_opcao => 'rótulo'
        31 => 'Empreitada Global',
        32 => 'Administração',
        33 => 'Preço Unitário',
        34 => 'Parada',
    ],
    'estado_obra_options' => [
        35  => 'RS',
        36  => 'PR',
        37  => 'SC',
        38  => 'SP',
        39  => 'RJ',
        40  => 'MG',
        41  => 'ES',
        42  => 'MT',
        43  => 'GO',
        44  => 'MS',
        45  => 'DF',
        46  => 'BA',
        47  => 'AL',
        48  => 'SE',
        49  => 'PE',
        50  => 'PB',
        51  => 'RN',
        52  => 'CE',
        53  => 'PI',
        54  => 'TO',
        55  => 'AM',
        56  => 'PA',
        57  => 'RR',
        58  => 'RO',
        59  => 'AP',
        60  => 'AC',
        212 => 'MA',
        213 => 'Diversos',
        214 => 'Internacional',
    ],

    'sync' => [
        // de quanto em quanto tempo o agendador dispara
        'interval_minutes' => env('PIPEDRIVE_SYNC_INTERVAL_MINUTES', 60),

        // janela de busca (olhar para trás) para garantir que nada ficou para trás
        'lookback_hours'   => env('PIPEDRIVE_SYNC_LOOKBACK_HOURS', 48),

        // paginação da API
        'page_limit'       => env('PIPEDRIVE_SYNC_PAGE_LIMIT', 100),
    ],
];
