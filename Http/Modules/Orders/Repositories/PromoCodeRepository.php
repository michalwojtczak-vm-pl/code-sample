<?php

namespace App\Http\Modules\Orders\Repositories;

use App\Models\PromoCode;
use Carbon\Carbon;

class PromoCodeRepository
{
    public function getActiveByCodeAndIds(string $code, array $ids): ?PromoCode
    {
        return PromoCode::fromRequestCompany()
                        ->whereIn('id', $ids)
                        ->where('code', $code)
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('expired_at')
                                  ->orWhereDate('expired_at', '>', Carbon::now());
                        })
                        ->first();
    }
}
