<?php

namespace App\Http\Modules\Payments\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    public function findByUid(string $uuid): ?Payment
    {
        return Payment::where('uid', $uuid)->first();
    }
}
