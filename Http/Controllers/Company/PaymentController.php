<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Modules\Payments\Managers\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @var PaymentManager
     */
    private $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function confirmPayment(Request $request, string $provider, string $companyUid)
    {
        return response()->json($this->paymentManager->confirmPayment($request->all(), $provider, $companyUid));
    }
}
