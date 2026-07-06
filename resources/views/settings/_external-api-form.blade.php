<div class="row g-2 mb-2">
    <div class="col-md-4">
        <label class="form-label">{{ __('external_api.connection_name') }} *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $connection?->name) }}" required maxlength="150">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('external_api.linked_customer') }} *</label>
        <select name="customer_id" class="form-select" required>
            <option value="">{{ __('external_api.select_customer') }}</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" @selected(old('customer_id', $connection?->customer_id) == $customer->id)>{{ $customer->company_name }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('external_api.customer_hint') }}</div>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <label class="form-check form-switch mb-2">
            <input type="checkbox" name="is_active" class="form-check-input" value="1" @checked(old('is_active', $connection?->is_active ?? true))>
            <span class="form-check-label">{{ __('settings.active') }}</span>
        </label>
    </div>
</div>
<div class="row g-2">
    <div class="col-12"><label class="form-label">{{ __('external_api.permissions_col') }}</label></div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="view_customer" class="form-check-input" value="1" @checked(old('view_customer', $connection?->view_customer ?? true))>
            <span class="form-check-label">{{ __('external_api.perm_customer') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="edit_customer" class="form-check-input" value="1" @checked(old('edit_customer', $connection?->edit_customer ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_edit_customer') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="view_directory" class="form-check-input" value="1" @checked(old('view_directory', $connection?->view_directory ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_directory') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="edit_directory" class="form-check-input" value="1" @checked(old('edit_directory', $connection?->edit_directory ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_edit_directory') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="view_orders" class="form-check-input" value="1" @checked(old('view_orders', $connection?->view_orders ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_orders') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="view_shipments" class="form-check-input" value="1" @checked(old('view_shipments', $connection?->view_shipments ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_shipments') }}</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check">
            <input type="checkbox" name="view_shipment_costs" class="form-check-input" value="1" @checked(old('view_shipment_costs', $connection?->view_shipment_costs ?? false))>
            <span class="form-check-label">{{ __('external_api.perm_shipment_costs') }}</span>
        </label>
    </div>
</div>
