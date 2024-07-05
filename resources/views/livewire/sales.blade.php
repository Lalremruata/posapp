<div class="relative w-96 max-w-lg px-1 pt-1">
    <input wire:model.live.throttle.300ms="search" type="text" id="search-input"
        class="block w-full flex-1 py-2 px-3 mt-2 outline-none border-none rounded-md bg-slate-100"
        placeholder="Start Typing..." autofocus/>
    <div class="absolute mt-2 w-full overflow-hidden rounded-md bg-white">
        @if(!empty($results))
            @foreach ($results as $result )
                <div wire:click="selectProduct({{ $result->id }})"
                    class="cursor-pointer py-2 px-3 hover:bg-slate-100">
                    <p class="text-sm font-medium text-gray-600">{{ $result->product_name }}</p>
                </div>
            @endforeach
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        window.livewire.on('productSelected', function () {
            document.getElementById('search-input').focus();
        });
    });
</script>
