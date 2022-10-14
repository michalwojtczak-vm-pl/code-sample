<?php

namespace App\Http\Modules\Payments\Managers;

use App\Enums\PaymentProvider;
use App\Http\Modules\Payments\Integrations\CashBillPayment;
use App\Http\Modules\Payments\Integrations\OnlinePaymentInterface;
use App\Http\Modules\Payments\Integrations\P24Payment;

class PaymentProviderFactory
{
    public static function getProvider(string $type): OnlinePaymentInterface
    {
        switch ($type) {
            case PaymentProvider::PRZELEWY_24:
                return new P24Payment();
            case PaymentProvider::CASHBILL:
                return new CashBillPayment();
        }

        throw new \InvalidArgumentException('Incorrect payment provider');
    }
}
