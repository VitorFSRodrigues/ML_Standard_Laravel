<?php

namespace App\Http\Controllers;

use App\Models\Pergunta;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerguntasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('perguntas.index');
    }

    /**
     * (Opcional) Form de criação tradicional. Se usar modais Livewire, pode não usar.
     */
    public function create(): View
    {
        return view('perguntas.create');
    }

    /**
     * Persistência via HTTP (opcional se usar modal Livewire).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Pergunta::create($data);

        return redirect()
            ->route('perguntas.index')
            ->with('success', 'Pergunta criada com sucesso.');
    }

    /**
     * (Opcional) Exibir 1 registro.
     */
    public function show(Pergunta $pergunta): View
    {
        return view('perguntas.show', compact('pergunta'));
    }

    /**
     * (Opcional) Form de edição tradicional. Se usar modais Livewire, pode não usar.
     */
    public function edit(Pergunta $pergunta): View
    {
        return view('perguntas.edit', compact('pergunta'));
    }

    /**
     * Atualização via HTTP (opcional se usar modal Livewire).
     */
    public function update(Request $request, Pergunta $pergunta): RedirectResponse
    {
        $data = $this->validated($request, $pergunta->id);
        $pergunta->update($data);

        return redirect()
            ->route('perguntas.index')
            ->with('success', 'Pergunta atualizada com sucesso.');
    }

    /**
     * Remoção via HTTP (opcional se usar modal Livewire).
     */
    public function destroy(Pergunta $pergunta): RedirectResponse
    {
        $pergunta->delete();

        return redirect()
            ->route('perguntas.index')
            ->with('success', 'Pergunta excluída com sucesso.');
    }

    /**
     * Regras de validação centralizadas.
     */
    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'peso'      => ['required', 'integer', 'min:0'],
            'padrao'    => ['sometimes', 'boolean'], // checkbox → 0/1; cast em Model trata como bool
        ], [], [
            'descricao' => 'Descrição',
            'peso'      => 'Peso',
            'padrao'    => 'Padrão',
        ]);
    }
}
