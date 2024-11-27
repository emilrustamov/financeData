<div class="">
    <div class="row g-3 align-items-center">
        @foreach ($currencies as $currency)
        <div class="col-3">
            <div class="d-flex align-items-center">
                <label for="rate-{{ $currency['id'] }}" class="form-label me-2 mb-0">{{ $currency['currency'] }}</label>
                <input 
                    type="number" 
                    step="0.01" 
                    class="form-control"
                    id="rate-{{ $currency['id'] }}"
                    wire:change="updateRate({{ $currency['id'] }}, $event.target.value)"
                    value="{{ $currency['rate'] }}"
                >
            </div>
        </div>
        @endforeach
    </div>
</div>
