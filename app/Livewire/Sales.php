<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class Sales extends Component
{
    public $search;
    public $results = [];
    public $selectedProduct;

    public function updatedSearch()
    {
        if(strlen($this->search) > 2){
            $this->results = Product::where('product_name', 'like','%'. $this->search . '%')->get();
        } else {
            $this->results = [];
        }
    }

    public function selectProduct($productId)
    {
        $this->selectedProduct = Product::find($productId);
        $this->search = ''; // Clear the search field
        $this->results = []; // Clear the search results

        // Add the selected product to the cart
        // Assuming you have a Cart model or similar logic
        // \App\Models\Cart::create([
        //     'product_id' => $this->selectedProduct->id,
        //     'quantity' => 1, // Example: Add 1 item
        // ]);

        // $this->emit('productSelected', $this->selectedProduct);
        $this->emit('productSelected', $this->selectedProduct);
    }

    public function render()
    {
        return view('livewire.sales');
    }
}

