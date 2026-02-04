<?php

namespace App\Livewire\Powergrid;

use App\Models\TriagemPergunta;
use App\Services\TriagemScoring;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Components\{Rules\Rule, Rules\Actions};

final class TriagemRespostasTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    private ?TriagemScoring $scoring = null;
    private float $pesoNA = 0.0;
    public string $tableName = 'triagem-respostas-table';
    public int $triagemId; // receber da view show

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(20)
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return TriagemPergunta::query()
            ->where('triagem_id', $this->triagemId)
            ->with(['pergunta:id,descricao,peso,padrao'])
            ->whereHas('pergunta', fn($q) => $q->where('padrao', true))
            ->orderBy('pergunta_id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        // calcula 1x por render
        $this->scoring ??= app(TriagemScoring::class);
        $this->pesoNA = $this->scoring->computePesoNA($this->triagemId);

        return PowerGrid::fields()
            ->add('id')
            ->add('pergunta.descricao')
            ->add('pergunta.peso')
            ->add('resposta') // V/F/NA
            ->add('subtotal', fn (TriagemPergunta $row) => $this->scoring->computeSubtotal($row, $this->pesoNA))
            ->add('observacao');
    }

    public function columns(): array
    {
        
        return [
            Column::make('Descrição', 'pergunta.descricao')->searchable(),
            Column::make('Peso', 'pergunta.peso')->sortable(),
            // ✅ Resposta editável (EditOnClick)
            Column::make('Resposta', 'resposta')
                ->sortable()
                ->editOnClick(true),

            Column::make('Sub-Total', 'subtotal')->sortable(),

            // Observação - texto editable
            Column::make('Observação', 'observacao')
                ->searchable()
                ->editOnClick(true),
            
            // Coluna de ações
            // Column::action('Ações'),
        ];
    }

    public function updateResposta(string|int $id, string $valor): void
    {
        $permitidos = ['V', 'F', 'NA'];
        $val = in_array(mb_strtoupper(trim($valor)), $permitidos, true) ? mb_strtoupper(trim($valor)) : 'NA';

        $row = TriagemPergunta::findOrFail((int) $id);
        $row->update(['resposta' => $val]);

        // Atualiza grid + resumo
        $this->dispatch('pg:eventRefresh', id: $this->tableName);
        $this->dispatch('triagem-resumo:refresh', triagemId: $this->triagemId);
    }
    /**
     * Hook chamado quando uma célula editável é alterada.
     * name => nome do campo, id => PK da linha, value => novo valor
     */
    public function onUpdatedEditable(string|int $id, string $field, string $value): void
    {
        if ($field === 'observacao') {
            TriagemPergunta::findOrFail((int) $id)
                ->update(['observacao' => mb_substr($value, 0, 255)]);

            $this->dispatch('pg:eventRefresh', id: $this->tableName);
            $this->dispatch('triagem-resumo:refresh', triagemId: $this->triagemId);
            $this->dispatch('reloadPowergrid');
            return;
        }

        if ($field === 'resposta') {
            // Reaproveita a mesma regra (V/F/NA ou NA)
            $this->updateResposta($id, $value);
            $this->dispatch('reloadPowergrid');
            return;
        }

        $this->dispatch('reloadPowergrid');
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
    }

    // ❌ Botão "Alterar" (comentado caso mudem de ideia)
    /*
    public function actions($row): array
    {
        return [
            Button::add('alterar_resposta')
                ->slot('Alterar')
                ->class('btn btn-xs btn-primary')
                ->openModal('modal.triagem.editar-resposta', [
                    'triagemPerguntaId' => $row->id,
                ]),
        ];
    }
    */

    /*
    public function actionRules($row): array
    {
       return [
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}