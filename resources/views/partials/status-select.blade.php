@props(['name' => 'status', 'group' => 'shipment', 'selected' => null, 'required' => false])

<select name="{{ $name }}" class="form-select" @if($required) required @endif>
    @foreach(config('ticari.' . ($group === 'order' ? 'order_statuses' : 'shipment_statuses')) as $s)
    <option value="{{ $s }}" @selected(old($name, $selected) === $s)>{{ status_label($s, $group) }}</option>
    @endforeach
</select>
