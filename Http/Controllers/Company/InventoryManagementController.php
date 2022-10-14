<?php

namespace App\Http\Controllers\Company;

use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Http\Modules\Company\InventoryManagement\Managers\InventoryProductManager;
use App\Models\InventoryProduct;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InventoryManagementController extends Controller
{
    /**
     * @var InventoryProductManager
     */
    private $inventoryProductManager;

    public function __construct(InventoryProductManager $inventoryProductManager)
    {
        $this->inventoryProductManager = $inventoryProductManager;
    }

    public function getList(Request $request, Restaurant $restaurant)
    {
        Gate::authorize('get', $restaurant);

        return response()->json($this->inventoryProductManager->getAll($restaurant, $request->all()));
    }

    public function create(Request $request, Restaurant $restaurant)
    {
        Gate::authorize('get', $restaurant);

        $data = $this->validate($request, [
            'name' => 'required|string',
            'sku' => 'nullable|string',
            'unit' => ['required', Rule::in(UnitType::all())],
            'amount' => 'nullable|numeric',
        ]);

        return response()->json($this->inventoryProductManager->create($restaurant, $data));
    }

    public function edit(Request $request, Restaurant $restaurant, InventoryProduct $product)
    {
        Gate::authorize('get', $restaurant);
        Gate::authorize('update', $product);

        $data = $this->validate($request, [
            'name' => 'required|string',
            'sku' => 'nullable|string',
        ]);

        return response()->json($this->inventoryProductManager->update($product, $data));
    }

    public function changeAmount(Request $request, Restaurant $restaurant, InventoryProduct $product)
    {
        Gate::authorize('get', $restaurant);
        Gate::authorize('update', $product);

        $data = $this->validate($request, [
            'amount' => 'required|numeric',
            'unit' => ['required', Rule::in(UnitType::all())],
        ]);

        return response()->json($this->inventoryProductManager->changeAmount($product, $data));
    }

    public function delete(Restaurant $restaurant, InventoryProduct $product)
    {
        Gate::authorize('get', $restaurant);
        Gate::authorize('delete', $product);

        return response()->json($this->inventoryProductManager->delete($product));
    }

    private function validatePayload(Request $request)
    {
        return $this->validate($request, [
            'name' => 'required|string',
            'sku' => 'nullable|string',
            'unit' => ['required', Rule::in(UnitType::all())],
            'amount' => 'nullable|numeric',
        ]);
    }
}
