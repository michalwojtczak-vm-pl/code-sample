<?php declare(strict_types=1);


namespace App\Http\Modules\Orders\Repositories;


use App\Http\Modules\Common\Repositories\BasicRepository;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;

class ProductRepository extends BasicRepository
{
    public function getProductById(?int $id)
    {
        return Product::with('addonGroups', 'addonGroups.addons')->find($id);
    }

    public function getCategories()
    {
        return Category::fromRequestCompany()->get();
    }

    public function getVisibleProducts(int $restaurantId, array $params)
    {
        $builder = Product::fromRequestCompany()->with('addonGroups', 'addonGroups.addons', 'categories');
        $builder = $builder->where('visible', true);
        $builder = $builder->where('restaurant_id', $restaurantId);

        $builder = $this->filterLike($builder, $params, 'search', 'name');

        return $builder->orderBy('order', 'asc')->get();
    }

    public function getRestaurants()
    {
        return Restaurant::fromRequestCompany()->get();
    }

    public function getRestaurantById(int $id): ?Restaurant
    {
        return Restaurant::fromRequestCompany()->where('id', $id)->first();
    }
}
