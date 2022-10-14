<?php declare(strict_types=1);


namespace App\Http\Modules\Company\InventoryManagement\Repositories;


use App\Http\Modules\Common\Repositories\BasicRepository;
use App\Models\InventoryProduct;
use App\Models\Restaurant;

class InventoryProductRepository extends BasicRepository
{
    public function getForParams(Restaurant $restaurant, array $params)
    {
        $query = InventoryProduct::fromThisCompany()->where('restaurant_id', $restaurant->getKey());

        $query = $query->when(!empty($params['search']), function ($query) use ($params) {
            $nameLike = '%'. htmlspecialchars($params['search'], ENT_QUOTES, "UTF-8"). '%';
            return $query->where(function ($q) use ($nameLike) {
                $q->where('sku', 'like', $nameLike)
                    ->orWhere('name', 'like', $nameLike);
            });
        });

        $query = $this->applySorting($query, $params);
        $total = $query->count();
        $result = $this->applyPaging($query, $params)->get();

        return [$result, $total];
    }

    public function findOne(int $id): ?InventoryProduct
    {
        return InventoryProduct::fromThisCompany()->where('id', $id)->first();
    }
}
