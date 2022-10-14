<?php

namespace App\Http\Controllers\Company;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionModule;
use App\Http\Controllers\Controller;
use App\Http\Modules\Company\Order\Managers\OrderManager;
use App\Http\Modules\Subscriptions\Managers\SubscriptionChecker;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrdersController extends Controller
{
    /**
     * @var OrderManager
     */
    private $orderManager;

    public function __construct(OrderManager $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    public function getList(Request $request, Restaurant $restaurant)
    {
        Gate::authorize('get', $restaurant);

        return response()->json($this->orderManager->getList($request->all(), $restaurant));
    }

    public function getListForPos(Request $request, Restaurant $restaurant)
    {
        Gate::authorize('get', $restaurant);
        SubscriptionChecker::checkAccess( SubscriptionModule::POS_APP);

        return response()->json($this->orderManager->getListForPos($request->all(), $restaurant));
    }

    public function createFromPos(Request $request, Restaurant $restaurant)
    {
        Gate::authorize('get', $restaurant);
        SubscriptionChecker::checkAccess( SubscriptionModule::POS_APP);

        $data = $this->validate($request, [
            'deliveryType' => [Rule::in(DeliveryType::allTypes())],
            'paymentMethod' => [Rule::in(PaymentMethod::allTypes())],
            'items' => 'required|array'
        ]);

        $this->storeCompanyUidIntoSession(Auth::user()->company->uid);

        return response()->json($this->orderManager->createFromPos($data, $restaurant));
    }

    public function getSingle(Restaurant $restaurant, Order $order)
    {
        Gate::authorize('get', $order);

        return response()->json($this->orderManager->getSingle($order));
    }

    public function acceptOrder(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('update', $order);

        $data = $this->validate($request, [
            'time' => 'nullable|numeric'
        ]);

        return response()->json($this->orderManager->acceptOrder($order, $data['time']));
    }

    public function changeDeclaratedTime(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('update', $order);

        $data = $this->validate($request, [
            'time' => 'required|string'
        ]);

        return response()->json($this->orderManager->changeDeclaratedTime($order, $data['time']));
    }

    public function cancelOrder(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('update', $order);

        $data = $this->validate($request, [
            'reason' => 'required|string'
        ]);

        return response()->json($this->orderManager->cancelOrder($order, $data['reason']));
    }

    public function changeStatus(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('update', $order);

        $data = $this->validate($request, [
            'status' => Rule::in(OrderStatus::all())
        ]);

        return response()->json($this->orderManager->changeStatus($order, $data['status']));
    }

    public function update(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('update', $order);

        $data = $this->validate($request, [
            'payment_status' => ['sometimes', Rule::in(PaymentStatus::all())]
        ]);

        return response()->json($this->orderManager->update($order, $data));
    }

    public function deleteTestProduct(Request $request, Restaurant $restaurant, Order $order)
    {
        Gate::authorize('delete', $order);

        return response()->json($this->orderManager->delete($order));
    }
}
