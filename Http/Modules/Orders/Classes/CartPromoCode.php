<?php

namespace App\Http\Modules\Orders\Classes;

use App\Enums\PromoCodeType;
use App\Models\ClientUser;
use App\Models\PromoCode;

class CartPromoCode
{
    /** @var PromoCode */
    protected $promoCode;
    /**
     * @var float
     */
    protected $overallOrderSum;
    /**
     * @var bool
     */
    private $errorIfIncorrect;


    public function __construct(?int $promoCodeId = null, ?float $overallOrderSum = null, ?ClientUser $clientUser = null, bool $errorIfIncorrect = true)
    {
        $this->overallOrderSum = $overallOrderSum;
        $this->errorIfIncorrect = $errorIfIncorrect;

        if ($promoCodeId) {
            $this->parse($promoCodeId, $clientUser);
        }
    }

    private function parse(?int $id, ?ClientUser $clientUser = null)
    {
        $this->setPromoCode(PromoCode::fromRequestCompany()->find($id), $clientUser);
    }

    private function setPromoCode(?PromoCode $promoCode, ?ClientUser $clientUser = null)
    {
        if ($promoCode && !$this->canUseCode($promoCode, $clientUser)) {
            if (!$this->errorIfIncorrect) {
                $this->promoCode = null;
                return;
            }

            throw new \InvalidArgumentException('Kod rabatowy nie jest już aktywny.');
        }

        $this->promoCode = $this->hasMinOrderFulfilled($promoCode, $this->overallOrderSum) ? $promoCode : null;
    }

    public function getDiscountAmount(): float
    {
        if (!$this->promoCode) {
            return 0;
        }

        switch ($this->promoCode->type) {
            case PromoCodeType::DISCOUNT_PERCENT:
                return $this->overallOrderSum * ($this->promoCode->amount / 100);
            case PromoCodeType::DISCOUNT_VALUE:
                return $this->promoCode->amount;
        }

        return 0;
    }

    public function hasFreeDeliveryCode(): bool
    {
        return $this->promoCode && $this->promoCode->type === PromoCodeType::FREE_DELIVERY;
    }

    public function canUseCode(PromoCode $code, ?ClientUser $clientUser = null): bool
    {
        if ($code->connected_to_user_id && (!$clientUser || $code->connected_to_user_id !== $clientUser->getKey())) {
            return false;
        }

        if (!$code->can_use_multiple_times && $clientUser && $clientUser->promoCodeUsages->where('promo_code_id', $code->getKey())->isNotEmpty()) {
            return false;
        }

        return $code->usage_limit === 0 || $code->usage_limit > $code->usages;
    }

    public function hasMinOrderFulfilled(PromoCode $code, float $orderSummary): bool
    {
        return $code->min_order_amount <= $orderSummary;
    }

    public function getPromoCode(): ?PromoCode
    {
        return $this->promoCode;
    }
}
