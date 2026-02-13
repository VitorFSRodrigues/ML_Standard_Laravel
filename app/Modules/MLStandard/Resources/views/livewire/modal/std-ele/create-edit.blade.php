<div class="p-3">
  <h5 class="mb-3">{{ $stdELEId ? 'Editar STD Elétrica' : 'Novo STD Elétrica' }}</h5>

  {{-- CHAVES --}}
  <div class="card mb-3">
    <div class="card-header">
      <strong>Chaves do Standard</strong>
      <small class="text-muted d-block">Digite livremente (mantemos FK automaticamente)</small>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-4 form-group">
          <label>Tipo</label>
          <input type="text" class="form-control" list="lst_ele_tipos" wire:model.defer="form.tipo_nome" placeholder="Ex: ELETRODUTO">
          <datalist id="lst_ele_tipos">
            @foreach($tipos as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.tipo_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-4 form-group">
          <label>Material</label>
          <input type="text" class="form-control" list="lst_ele_materiais" wire:model.defer="form.material_nome" placeholder="Ex: GALVANIZADO">
          <datalist id="lst_ele_materiais">
            @foreach($materiais as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.material_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-4 form-group">
          <label>Conexão</label>
          <input type="text" class="form-control" list="lst_ele_conexoes" wire:model.defer="form.conexao_nome" placeholder="Ex: BUCHA TERMINAL">
          <datalist id="lst_ele_conexoes">
            @foreach($conexoes as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.conexao_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 form-group">
          <label>Espessura</label>
          <input type="text" class="form-control" list="lst_ele_espessuras" wire:model.defer="form.espessura_nome" placeholder="Ex: PESADO">
          <datalist id="lst_ele_espessuras">
            @foreach($espessuras as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.espessura_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-4 form-group">
          <label>Extremidade</label>
          <input type="text" class="form-control" list="lst_ele_extremidades" wire:model.defer="form.extremidade_nome" placeholder="Ex: ROSCADO">
          <datalist id="lst_ele_extremidades">
            @foreach($extremidades as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.extremidade_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-4 form-group">
          <label>Dimensão</label>
          <input type="text" class="form-control" list="lst_ele_dimensoes" wire:model.defer="form.dimensao_nome" placeholder='Ex: 2"'>
          <datalist id="lst_ele_dimensoes">
            @foreach($dimensoes as $n) <option value="{{ $n }}"></option> @endforeach
          </datalist>
          @error('form.dimensao_nome') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>
    </div>
  </div>

  {{-- STD --}}
  <div class="card-body">
    <div class="row">
      <div class="col-md form-group">
        <label>STD</label>
        <input type="number" step="0.0001" min="0" class="form-control" wire:model.defer="form.std">
        @error('form.std') <span class="text-danger">{{ $message }}</span> @enderror
      </div>
    </div>
  </div>

  {{-- CARGOS --}}
  <div class="card mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <strong>Cargos</strong>
        <small class="text-muted d-block">
          Aceita: <b>10,05%</b> ou <b>10,05</b>. A soma precisa ser <b>100%</b>.
        </small>
      </div>

      @if($this->cargosSumOk)
        <span class="badge badge-success" style="font-size: 14px;">
          ✅ Soma atual: {{ $this->cargosSumFmt }}
        </span>
      @else
        <span class="badge badge-warning" style="font-size: 14px;">
          ⚠️ Soma atual: {{ $this->cargosSumFmt }}
        </span>
      @endif
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-3 form-group">
          <label>Enc. Mec.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encarregado_mecanica" placeholder="Ex: 10,00%">
          {{-- <input type="text" class="form-control" wire:model.defer="form.encarregado_mecanica" placeholder="Ex: 10,00%"> --}}
          @error('form.encarregado_mecanica') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Enc. Tub.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encarregado_tubulacao" placeholder="Ex: 10,00%">
          @error('form.encarregado_tubulacao') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Enc. Ele.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encarregado_eletrica" placeholder="Ex: 10,00%">
          @error('form.encarregado_eletrica') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Enc. And.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encarregado_andaime" placeholder="Ex: 10,00%">
          @error('form.encarregado_andaime') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Enc. Civ.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encarregado_civil" placeholder="Ex: 10,00%">
          @error('form.encarregado_civil') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Líder</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.lider" placeholder="Ex: 10,00%">
          @error('form.lider') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Mec. Ajust.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.mecanico_ajustador" placeholder="Ex: 10,00%">
          @error('form.mecanico_ajustador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Mec. Mont.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.mecanico_montador" placeholder="Ex: 10,00%">
          @error('form.mecanico_montador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Encan.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.encanador" placeholder="Ex: 10,00%">
          @error('form.encanador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Cald.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.caldeireiro" placeholder="Ex: 10,00%">
          @error('form.caldeireiro') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Lixid.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.lixador" placeholder="Ex: 10,00%">
          @error('form.lixador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Mont.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.montador" placeholder="Ex: 10,00%">
          @error('form.montador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>S.ER</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.soldador_er" placeholder="Ex: 10,00%">
          @error('form.soldador_er') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>S.TIG</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.soldador_tig" placeholder="Ex: 10,00%">
          @error('form.soldador_tig') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>S.MIG</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.soldador_mig" placeholder="Ex: 10,00%">
          @error('form.soldador_mig') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Pont.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.ponteador" placeholder="Ex: 10,00%">
          @error('form.ponteador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Elet. F/C</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.eletricista_controlista" placeholder="Ex: 10,00%">
          @error('form.eletricista_controlista') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Elet Mont.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.eletricista_montador" placeholder="Ex: 10,00%">
          @error('form.eletricista_montador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Instr.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.instrumentista" placeholder="Ex: 10,00%">
          @error('form.instrumentista') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Mont. And.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.montador_de_andaime" placeholder="Ex: 10,00%">
          @error('form.montador_de_andaime') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Pintor</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.pintor" placeholder="Ex: 10,00%">
          @error('form.pintor') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Jatista</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.jatista" placeholder="Ex: 10,00%">
          @error('form.jatista') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Pedreiro</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.pedreiro" placeholder="Ex: 10,00%">
          @error('form.pedreiro') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Carpinteiro</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.carpinteiro" placeholder="Ex: 10,00%">
          @error('form.carpinteiro') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Armador</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.armador" placeholder="Ex: 10,00%">
          @error('form.armador') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-3 form-group">
          <label>Ajud.</label>
          <input type="text" class="form-control" wire:model.live.debounce.300ms="form.ajudante" placeholder="Ex: 10,00%">
          @error('form.ajudante') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>
