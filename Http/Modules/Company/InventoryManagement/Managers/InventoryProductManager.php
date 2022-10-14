<?php declare(strict_types=1);


namespace App\Http\Modules\Company\InventoryManagement\Managers;


use App\Enums\UnitType;
use App\Http\Modules\Common\Classes\Paginator;
use App\Http\Modules\Company\InventoryManagement\Repositories\InventoryProductRepository;
use App\Http\Resources\Company\CompanyInventoryProductResource;
use App\Models\InventoryProduct;
use App\Models\Restaurant;

class InventoryProductManager
{
    /**
     * @var InventoryProductRepository
     */
    private $inventoryProductRepository;

    public function __construct(InventoryProductRepository $inventoryProductRepository)
    {
        $this->inventoryProductRepository = $inventoryProductRepository;
    }

    public function getAll(Restaurant $restaurant, array $params)
    {
        list($data, $total) = $this->inventoryProductRepository->getForParams($restaurant, $params);

        return new Paginator($params, $total, CompanyInventoryProductResource::collection($data));
    }

    public function getOne(int $id): ?InventoryProduct
    {
        return $this->inventoryProductRepository->findOne($id);
    }

    public function create(Restaurant $restaurant, array $data)
    {
        $product = new InventoryProduct($data);
        list($amount, $unit) = $this->transferAmountToCommonUnit($data['amount'], $data['unit']);
        $product->amount = $amount;
        $product->unit = $unit;
        $product->company()->associate(auth()->user()->company);
        $product->restaurant()->associate($restaurant);
        $product->save();

        return new CompanyInventoryProductResource($product);
    }

    public function update(InventoryProduct $product, array $data)
    {
        $product->update($data);

        return new CompanyInventoryProductResource($product);
    }

    public function delete(InventoryProduct $product)
    {
        $product->delete();

        return true;
    }

    public function transferAmountToCommonUnit(float $amount, string $unit): array
    {
        switch ($unit) {
            case UnitType::KILOGRAMS:
                return [$amount * 1000, UnitType::GRAMS];
            case UnitType::LITERS:
                return [$amount * 1000, UnitType::MILILITERS];
        }

        return [$amount, $unit];
    }

    public function changeAmount(InventoryProduct $product, array $data)
    {
        list($amount, $unit) = $this->transferAmountToCommonUnit($data['amount'], $data['unit']);

        $product->amount += $amount;
        $product->save();

        return new CompanyInventoryProductResource($product);
    }
}
