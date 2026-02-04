{{-- resources/views/livewire/modal/triagem/move.blade.php --}}
<div class="p-3">
  <h5 class="mb-3">Mover orçamento</h5>

  <div class="mb-2">
    <div><strong>Local atual:</strong> {{ ucfirst($atual) }}</div>
  </div>

  <div class="form-group mb-2">
    <label class="mb-1">Mover para</label>
    <select class="form-control" wire:model.defer="destino">
      @foreach($opcoes as $val => $label)
        @if($atual !== 'orcamento' || $val !== 'orcamento' || true)
          <option value="{{ $val }}">{{ $label }}</option>
        @endif
      @endforeach
    </select>
  </div>

  @if($destino === 'orcamento')
    <div class="form-group">
      <label class="mb-1">Orçamentista destino</label>
      <select class="form-control" wire:model.defer="orcamentistaId">
        <option value="">-- selecione --</option>
        @foreach($optsOrcamentistas as $opt)
          @if((int)($opt['id']) !== (int)($orcamentistaAtualId ?? 0))
            <option value="{{ $opt['id'] }}">{{ $opt['nome'] }}</option>
          @endif
        @endforeach
      </select>
      @error('orcamentistaId') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
  @endif

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button class="btn btn-primary" wire:click="confirmar">Confirmar</button>
  </div>
</div>