<?php

namespace App\Livewire\Modal\Clientes;

use App\Models\Cliente;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $clienteId;
    public static function destroyOnClose(): bool { return true; }

    public function mount(int|string $clienteId): void
    { $this->clienteId = (int) $clienteId; }

    public function delete(): void
    {
        Cliente::findOrFail($this->clienteId)->delete();
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render() { return view('livewire.modal.clientes.confirm-delete'); }
}