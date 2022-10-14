<?php

namespace App\Http\Controllers\User;

use App\Events\OrderEvent;
use App\Http\Controllers\Controller;
use App\Http\Modules\Orders\Managers\PromoCodeManager;
use App\Http\Modules\Orders\Managers\UserOrderManager;
use App\Http\Requests\SyncOrderSummaryRequest;
use App\Models\Company;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * @var UserOrderManager
     */
    private $userOrderManager;
    /**
     * @var PromoCodeManager
     */
    private $promoCodeManager;

    public function __construct(UserOrderManager $userOrderManager, PromoCodeManager $promoCodeManager)
    {
        $this->userOrderManager = $userOrderManager;
        $this->promoCodeManager = $promoCodeManager;
        $this->middleware('auth:api_user')->only('getList');
    }

    public function getList()
    {
        return response()->json($this->userOrderManager->getAll());
    }

    public function getSingle(string $companyUid, string $orderUid)
    {
        return response()->json($this->userOrderManager->getSingle($orderUid));
    }

    public function addReview(Request $request, string $companyUid, string $orderUid)
    {
        $data = $this->validate($request, [
            'name' => 'required|string',
            'content' => 'nullable|string',
            'points' => 'required|numeric'
        ]);

        return response()->json($this->userOrderManager->addReview($orderUid, $data));
    }

    public function checkPromoCode(Request $request, string $companyUid)
    {
        $this->storeCompanyUidIntoSession($companyUid);

        $data = $this->validate($request, [
            'code' => 'required|string',
            'restaurant_id' => 'required|numeric',
            'items' => 'required|array'
        ]);

        return response()->json($this->promoCodeManager->checkPromoCode($data));
    }

    public function syncSummary(SyncOrderSummaryRequest $request, string $companyUid)
    {
        $this->storeCompanyUidIntoSession($companyUid);

        return response()->json($this->userOrderManager->syncOrderSummary($request->all()));
    }

    public function checkAddress(Request $request, string $companyUid)
    {
        $this->storeCompanyUidIntoSession($companyUid);

        $data = $this->validate($request, [
           'restaurant' => 'required|numeric',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'street_and_number' => 'nullable',
        ]);

        return response()->json($this->userOrderManager->checkAddress($data));
    }

    public function placeOrder(Request $request, string $companyUid)
    {
        $this->storeCompanyUidIntoSession($companyUid);

        return response()->json($this->userOrderManager->placeOrder($request->all(), false, $request->ip()));
    }
}
