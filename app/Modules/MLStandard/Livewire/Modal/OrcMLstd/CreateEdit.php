<?php

namespace App\Modules\MLStandard\Livewire\Modal\OrcMLstd;

use App\Modules\MLStandard\Models\OrcMLstd;
use App\Modules\MLStandard\Models\OrcMLstdItem;
use App\Models\Orcamentista;

use LivewireUI\Modal\ModalComponent;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CreateEdit extends ModalComponent
{
    public int|string|null $orcMLstdId = null;

    public array $form = [
        'numero_orcamento'  => '',
        'rev'               => 0,
        'orcamentista_id'   => null,
    ];

    public array $orcamentistas = [];

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string|null $orcMLstdId = null): void
    {
        $this->orcMLstdId = $orcMLstdId ? (int) $orcMLstdId : null;

        $this->orcamentistas = Orcamentista::query()
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->toArray();

        if ($this->orcMLstdId) {
            $row = OrcMLstd::findOrFail($this->orcMLstdId);

            $this->form = [
                'numero_orcamento' => $row->numero_orcamento,
                'rev'              => (int) $row->rev,
                'orcamentista_id'  => $row->orcamentista_id,
            ];
        }
    }

    private function rules(): array
    {
        return [
            'form.numero_orcamento' => [
                'required',
                'string',
                'max:255',
                Rule::unique('orc_ml_std', 'numero_orcamento')
                    ->where(fn ($q) => $q->where('rev', (int) $this->form['rev']))
                    ->ignore($this->orcMLstdId),
            ],
            'form.rev' => ['required', 'integer', 'min:0'],
            'form.orcamentista_id' => ['required', 'exists:orcamentistas,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate(
            $this->rules(),
            [
                'form.numero_orcamento.required' => 'Digite o número do orçamento.',
                'form.numero_orcamento.unique'   => 'Revisão do orçamento já existe.',
                'form.orcamentista_id.required'  => 'Selecione um orçamentista.',
                'form.orcamentista_id.exists'    => 'O orçamentista selecionado é inválido.',
            ]
        )['form'];

        DB::transaction(function () use ($data) {

            // ✅ EDIT
            if ($this->orcMLstdId) {
                OrcMLstd::findOrFail($this->orcMLstdId)->update($data);
                return;
            }

            // ✅ CREATE
            $novo = OrcMLstd::create($data);

            // ✅ 14.3 - CLONA ITENS DA REVISÃO ANTERIOR (MESMO ORÇAMENTO)
            $anterior = OrcMLstd::query()
                ->where('numero_orcamento', $data['numero_orcamento'])
                ->where('rev', '<', (int) $data['rev'])
                ->orderByDesc('rev')
                ->first();

            if (!$anterior) {
                return; // primeira revisão -> nada pra copiar
            }

            $jaTemItens = OrcMLstdItem::query()
                ->where('orc_ml_std_id', $novo->id)
                ->exists();

            if ($jaTemItens) {
                return; // segurança
            }

            $itensPrev = OrcMLstdItem::query()
                ->where('orc_ml_std_id', $anterior->id)
                ->orderBy('ordem')
                ->get();

            if ($itensPrev->isEmpty()) {
                return;
            }

            $now = now();
            $insert = [];

            foreach ($itensPrev as $item) {
                $insert[] = [
                    'orc_ml_std_id' => $novo->id,
                    'ordem'         => $item->ordem,
                    'disciplina'    => $item->disciplina,
                    'descricao'     => $item->descricao,
                    'ignorar_desc'  => $item->ignorar_desc,

                    'prob'          => $item->prob,

                    // ELE
                    'std_ele_tipo_id'        => $item->std_ele_tipo_id,
                    'std_ele_material_id'    => $item->std_ele_material_id,
                    'std_ele_conexao_id'     => $item->std_ele_conexao_id,
                    'std_ele_espessura_id'   => $item->std_ele_espessura_id,
                    'std_ele_extremidade_id' => $item->std_ele_extremidade_id,
                    'std_ele_dimensao_id'    => $item->std_ele_dimensao_id,
                    'std_ele'                => $item->std_ele,

                    // TUB
                    'std_tub_tipo_id'        => $item->std_tub_tipo_id,
                    'std_tub_material_id'    => $item->std_tub_material_id,
                    'std_tub_schedule_id'    => $item->std_tub_schedule_id,
                    'std_tub_extremidade_id' => $item->std_tub_extremidade_id,
                    'std_tub_diametro_id'    => $item->std_tub_diametro_id,

                    'hh_un' => $item->hh_un,
                    'kg_hh' => $item->kg_hh,
                    'kg_un' => $item->kg_un,
                    'm2_un' => $item->m2_un,

                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            OrcMLstdItem::insert($insert);
        });

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.orc-mlstd.create-edit');
    }
}


