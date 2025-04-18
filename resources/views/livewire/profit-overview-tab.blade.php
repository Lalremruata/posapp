<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($widgets as $widget)
        <div class="col-span-1 md:col-span-1">
            @livewire($widget)
        </div>
    @endforeach
</div>
