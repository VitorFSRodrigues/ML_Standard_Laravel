<?php

namespace App\Http\Controllers;

use App\Models\Triagem;
use App\Enums\CaracteristicaOrcamento;
use App\Enums\TipoServico;
use App\Enums\RegimeContrato;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TriagemController extends Controller
{
    /**
     * Lista / tela da triagem.
     * A view pode apenas renderizar o componente Livewire/PowerGrid (TriagemTable) quando você criar.
     */
    public function index()
    {
        return view('triagem.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Cria (HTTP). Mantido para compatibilidade.
     * Se você usa modais Livewire, provavelmente não usará este endpoint.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Triagem::create($data);

        return redirect()
            ->route('triagem.index')
            ->with('success', 'Registro de triagem criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Triagem $triagem)
    {
        $triagem->loadCount('triagemPerguntas'); // ->triagem_perguntas_count
        $triagem->local_formatado = collect([
            $triagem->clienteFinal?->municipio,
            $triagem->clienteFinal?->estado ? mb_strtoupper($triagem->clienteFinal->estado) : null,
        ])->filter()->implode(' - ');
        return view('triagem.show', compact('triagem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Triagem $triagem)
    {
        //
    }

    /**
     * Atualiza (HTTP). Mantido para compatibilidade.
     * Se você usa modais Livewire, provavelmente não usará este endpoint.
     */
    public function update(Request $request, Triagem $triagum): RedirectResponse
    {
        $data = $this->validated($request, $triagum->id);
        $triagum->update($data);

        return redirect()
            ->route('triagem.index')
            ->with('success', 'Registro de triagem atualizado com sucesso.');
    }

    /**
     * Exclui (HTTP). Mantido para compatibilidade.
     * Se você usa modais Livewire, provavelmente não usará este endpoint.
     */
    public function destroy(Triagem $triagum): RedirectResponse
    {
        $triagum->delete();

        return redirect()
            ->route('triagem.index')
            ->with('success', 'Registro de triagem excluído com sucesso.');
    }

        /**
     * Regras e normalização dos dados
     */
    private function validated(Request $request, ?int $id = null): array
    {
        $caracteristicas = array_map(fn($c) => $c->value, CaracteristicaOrcamento::cases());
        $tiposServico    = array_map(fn($c) => $c->value, TipoServico::cases());
        $regimes         = array_map(fn($c) => $c->value, RegimeContrato::cases());

        $data = $request->validate([
            'cliente_id'              => ['required', 'integer', 'exists:clientes,id'],
            'cliente_final_id'        => ['required', 'integer', 'exists:clientes,id'],
            'numero_orcamento'        => ['required', 'string', 'max:255'],
            'caracteristica_orcamento'=> ['required', 'string', Rule::in($caracteristicas)],
            'tipo_servico'            => ['required', 'string', Rule::in($tiposServico)],
            'regime_contrato'         => ['required', 'string', Rule::in($regimes)],
            'descricao_servico'       => ['required', 'string', 'max:255'],
            'descricao_resumida'      => ['required', 'string', 'max:255'],
            'condicao_pagamento_ddl'  => ['required', 'integer', 'min:0'],
            'data_inicio_obra'        => ['nullable', 'date'],
            'prazo_obra'              => ['required', 'integer', 'min:0'],
        ]);

        // (Opcional) Normalizações adicionais podem entrar aqui
        return $data;
    }
}
