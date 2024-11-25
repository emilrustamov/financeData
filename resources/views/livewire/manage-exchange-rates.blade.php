<div class="">
    <div class="row g-3">
        @foreach ($currencies as $currency)
        <div class="col-3">
            <div class="form-group">
                <label for="rate-{{ $currency['id'] }}" class="form-label">{{ $currency['currency'] }}</label>
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
