@props([
    'rowId',
    'field',
    'options' => [],
    'selected' => null,
    'edited' => false,
])

<select
    class="form-control form-control-sm"
    style="{{ $edited ? 'border:2px solid #0d6efd;background:#cfe2ff;' : '' }}"
    wire:change="cascadeSelectChanged({{ (int) $rowId }}, '{{ $field }}', $event.target.value)"
>
    <option value="">--</option>

    @foreach($options as $id => $label)
        <option value="{{ $id }}" @selected((string)$selected === (string)$id)>
            {{ $label }}
        </option>
    @endforeach
</select>
