<?php

namespace App\Http\Controllers\Company;

use App\Enums\SubscriptionModule;
use App\Http\Controllers\Controller;
use App\Http\Modules\Subscriptions\Managers\SubscriptionChecker;
use App\Http\Modules\Subscriptions\Managers\SubscriptionManager;
use App\Http\Resources\Company\CompanySubscriptionPlanResource;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionProduct;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    public function getPlans()
    {
        return response()->json($this->subscriptionManager->getPlans());
    }

    public function getPlan(SubscriptionPlan $plan)
    {
        return response()->json(new CompanySubscriptionPlanResource($plan));
    }

    public function getIntent()
    {
        return response()->json($this->subscriptionManager->createIntent());
    }

    public function createSubscription(Request $request)
    {
        $data = $this->validate($request, [
            'plan_id' => 'required|exists:subscription_plans,id',
            'selected_items' => 'nullable|array',
            'name' => 'required|string',
            'tax_number' => 'required|string',
            'street_number' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'postal_code' => 'required|string',
            'payment_method' => 'required|string',
            'card_holder_name' => 'required|string',
            'email' => 'required|string',
            'promo_code' => 'nullable|string',
        ]);

        return response()->json($this->subscriptionManager->createSubscription($data));
    }

    public function updateSubscription(Request $request)
    {
        $data = $this->validate($request, [
            'plan_id' => 'required|exists:subscription_plans,id',
            'selected_items' => 'nullable|array',
            'name' => 'required|string',
            'tax_number' => 'required|string',
            'street_number' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'postal_code' => 'required|string',
            'email' => 'required|string',
            'promo_code' => 'nullable|string',
        ]);

        return response()->json($this->subscriptionManager->updateSubscription($data));
    }

    public function checkPromoCode(Request $request)
    {
        $data = $this->validate($request, [
            'promo_code' => 'required|string',
        ]);

        return response()->json($this->subscriptionManager->checkPromoCode($data['promo_code']));
    }

    public function updateCard(Request $request)
    {
        $data = $this->validate($request, [
            'payment_method' => 'required|string',
            'card_holder_name' => 'required|string',
        ]);

        return response()->json($this->subscriptionManager->updateCard($data));
    }

    public function getCurrentSubInfo()
    {
        return response()->json($this->subscriptionManager->getCurrentSubInfo());
    }

    public function cancelSubscription()
    {
        return response()->json($this->subscriptionManager->cancelSubscription());
    }

    public function resumeSubscription()
    {
        return response()->json($this->subscriptionManager->resumeSubscription());
    }

    public function getAvailableModules()
    {
        $company = auth()->user()->company;

        return response()->json([
            'MOBILE_APP' => SubscriptionChecker::hasAccess(SubscriptionModule::MOBILE_APP, $company),
            'POS_APP' => SubscriptionChecker::hasAccess(SubscriptionModule::POS_APP, $company),
            'QR_MENU' => SubscriptionChecker::hasAccess(SubscriptionModule::QR_MENU, $company),
            'TABLE_ORDERS' => SubscriptionChecker::hasAccess(SubscriptionModule::TABLE_ORDERS, $company),
            'TABLE_RESERVATIONS' => SubscriptionChecker::hasAccess(SubscriptionModule::TABLE_RESERVATIONS, $company),
            'PROMO_CODES' => SubscriptionChecker::hasAccess(SubscriptionModule::PROMO_CODES, $company),
            'LOYALTY_PROGRAM' => SubscriptionChecker::hasAccess(SubscriptionModule::LOYALTY_PROGRAM, $company),
            'RESTAURANT_LIMIT' => SubscriptionChecker::restaurantLimit($company),
        ]);
    }
}
