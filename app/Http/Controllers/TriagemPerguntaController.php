<?php

namespace App\Http\Controllers;

use App\Models\Pergunta;
use App\Models\Triagem;
use App\Models\TriagemPergunta;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TriagemPerguntaController extends Controller
{
    /**
     * Lista (pode ser JSON para consumo via Livewire/Ajax).
     * Se preferir, troque para uma view própria.
     */
    public function index(Triagem $triagem): View
    {
        $respostas = TriagemPergunta::query()
            ->daTriagem($triagem->id)
            ->comPergunta()
            ->orderBy('pergunta_id')
            ->get();

        return view('triagem.respostas', [
            'triagem'   => $triagem,
            'respostas' => $respostas,
        ]);
    }
    /**
     * Inicializa a tabela triagem_pergunta com TODAS as perguntas ainda não vinculadas.
     * Útil na primeira visita à página /triagem/{id}.
     */
    public function init(Triagem $triagem): RedirectResponse
    {
        $jaVinculadas = TriagemPergunta::where('triagem_id', $triagem->id)
            ->pluck('pergunta_id')
            ->all();

        $faltantes = Pergunta::query()
            ->where('padrao', true)
            ->whereNotIn('id', $jaVinculadas)
            ->pluck('id')
            ->all();

        $payload = [];
        foreach ($faltantes as $perguntaId) {
            $payload[] = [
                'triagem_id'  => $triagem->id,
                'pergunta_id' => $perguntaId,
                'resposta'    => 'NA', // default
                'observacao'  => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (!empty($payload)) {
            // insert em lote
            TriagemPergunta::insert($payload);
        }

        return back()->with('success', 'Perguntas vinculadas à triagem com sucesso.');
    }

    /**
     * Upsert em lote (salva várias respostas de uma vez).
     * Espera payload:
     * itens: [
     *   { pergunta_id, resposta (V|F|NA), observacao? },
     *   ...
     * ]
     */
    public function storeMany(Request $request, Triagem $triagem): RedirectResponse
    {
        $data = $request->validate([
            'itens'                       => ['required','array','min:1'],
            'itens.*.pergunta_id'         => ['required','integer','exists:perguntas,id'],
            'itens.*.resposta'            => ['required','string', Rule::in(TriagemPergunta::RESPOSTAS)],
            'itens.*.observacao'          => ['nullable','string','max:255'],
        ], [], [
            'itens'               => 'Itens',
            'itens.*.pergunta_id' => 'Pergunta',
            'itens.*.resposta'    => 'Resposta',
            'itens.*.observacao'  => 'Observação',
        ]);

        // upsert baseado na unique(triagem_id, pergunta_id)
        $rows = [];
        foreach ($data['itens'] as $item) {
            $rows[] = [
                'triagem_id'  => $triagem->id,
                'pergunta_id' => (int) $item['pergunta_id'],
                'resposta'    => $item['resposta'],
                'observacao'  => $item['observacao'] ?? null,
                'updated_at'  => now(),
                'created_at'  => now(),
            ];
        }

        TriagemPergunta::upsert(
            $rows,
            ['triagem_id', 'pergunta_id'],           // unique key
            ['resposta', 'observacao', 'updated_at'] // cols a atualizar
        );

        return back()->with('success', 'Respostas salvas com sucesso.');
    }

    /**
     * Atualiza 1 resposta específica (edição linha a linha).
     */
    public function update(Request $request, TriagemPergunta $triagemPergunta): RedirectResponse
    {
        $data = $request->validate([
            'resposta'   => ['required','string', Rule::in(TriagemPergunta::RESPOSTAS)],
            'observacao' => ['nullable','string','max:255'],
        ]);

        $triagemPergunta->update($data);

        return back()->with('success', 'Resposta atualizada com sucesso.');
    }

    /**
     * Remove 1 vínculo (caso precise desvincular).
     */
    public function destroy(TriagemPergunta $triagemPergunta): RedirectResponse
    {
        $triagemPergunta->delete();

        return back()->with('success', 'Resposta removida com sucesso.');
    }
}
