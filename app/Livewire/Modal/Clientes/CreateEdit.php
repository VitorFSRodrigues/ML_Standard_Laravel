<?php

namespace App\Livewire\Modal\Clientes;

use App\Models\Cliente;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public int|string|null $clienteId = null;

    public array $form = [
        'nome_cliente' => '', 'nome_fantasia' => '', 'endereco_completo' => '', 'municipio' => '',
        'estado' => '', 'pais' => '', 'cnpj' => '',
    ];

    public static function destroyOnClose(): bool { return true; }

    public function mount(int|string|null $clienteId = null): void
    {
        if ($clienteId === null || $clienteId === '') { $this->clienteId = null; return; }
        $this->clienteId = (int) $clienteId;
        $c = Cliente::findOrFail($this->clienteId);
        $this->form = [
            'nome_cliente' => $c->nome_cliente,
            'nome_fantasia' => $c->nome_fantasia,
            'endereco_completo' => $c->endereco_completo,
            'municipio' => $c->municipio,
            'estado' => $c->estado,
            'pais' => $c->pais,
            'cnpj' => $c->cnpj,
        ];
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.nome_cliente' => 'required|string|max:255',
            'form.nome_fantasia'  => 'nullable|string|max:255',
            'form.endereco_completo' => 'required|string|max:255',
            'form.municipio' => 'required|string|max:255',
            'form.estado' => 'required|string|max:255',
            'form.pais' => 'required|string|max:255',
            'form.cnpj' => 'required|string|max:255',
        ])['form'];

        $this->clienteId !== null
            ? Cliente::findOrFail((int)$this->clienteId)->update($data)
            : Cliente::create($data);

        // Refresh PowerGrid
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render() { return view('livewire.modal.clientes.create-edit'); }
}