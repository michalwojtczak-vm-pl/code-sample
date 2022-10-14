<?php

namespace App\Http\Modules\Orders\Repositories;

use App\Http\Modules\Common\Repositories\BasicRepository;
use App\Models\Order;
use App\Models\User;

class OrderRepository extends BasicRepository
{
    public function getByUid(string $uid): ?Order
    {
        return Order::where('uid', $uid)->first();
    }

    public function getAll(User $user)
    {
        $query = Order::with('orderProducts', 'orderProducts.addonGroups')->where('user_id', $user->getKey());
        $query = $this->applySorting($query, ['sort_dir' => 'desc']);

        return $query->get();
    }
}
