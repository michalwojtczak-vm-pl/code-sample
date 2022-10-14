<?php declare(strict_types=1);


namespace App\Http\Modules\Orders\Classes;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderChangedEvent;
use App\Models\ClientUser;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductAddonGroup;
use App\Models\ProductAddonGroupAddon;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Cart
{
    /** @var Collection */
    protected $items;

    /** @var Delivery */
    protected $delivery;

    /** @var CartPromoCode */
    protected $promoCode;

    /** @var string */
    protected $paymentMethod;

    /** @var string */
    protected $comments;

    /** @var string */
    protected $source;

    /** @var string */
    protected $table;

    /** @var float */
    protected $tip;

    /** @var array */
    protected $personalData;

    /** @var Carbon */
    protected $realizationTime;

    /** @var Restaurant */
    protected $restaurant;
    /**
     * @var ClientUser|User|null
     */
    private $clientUser;
    /**
     * @var string
     */
    private $onesignal_player_id;
    /**
     * @var bool
     */
    private $errorIfIncorrect;
    /**
     * @var string
     */
    private $blikCode;

    public function __construct(array $params, $clientUser = null, bool $errorIfIncorrect = true)
    {
        $this->items = collect([]);
        $this->clientUser = $clientUser;
        $this->errorIfIncorrect = $errorIfIncorrect;
        $this->parse($params);
    }

    public function parse(array $params)
    {
        foreach ($params['items'] ?? [] as $item) {
            $this->add(new CartItem($item));
        }

        $this->restaurant = Restaurant::findOrFail($params['restaurant'] ?? null);
        $this->delivery = new Delivery($params['deliveryType'] ?? null, $params['deliveryAddress'] ?? null, false, $this->restaurant);
        $this->promoCode = new CartPromoCode($params['promoCodeId'] ?? null, $this->overallSum(), $this->clientUser, $this->errorIfIncorrect);
        $this->paymentMethod = $params['paymentMethod'] ?? null;
        $this->comments = $params['comments'] ?? null;
        $this->personalData = $params['personalData'] ?? null;
        $this->realizationTime = !empty($params['realizationTime']) ? Carbon::parse($params['realizationTime'], 'UTC')->setTimezone('Europe/Warsaw') : null;
        $this->source = isset($params['source']) ? $params['source'] : 'mobile';
        $this->table = isset($params['table']) ? $params['table'] : null;
        $this->tip = isset($params['tip']) ? $params['tip'] : 0;
        $this->onesignal_player_id = isset($params['onesignal_player_id']) ? $params['onesignal_player_id'] : null;
        $this->blikCode = isset($params['blik_code']) ? $params['blik_code'] : null;
    }

    public function add(CartItem $item)
    {
        $this->items->add($item);
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function isCashPayment(): bool
    {
        return $this->getPaymentMethod() === PaymentMethod::CASH || $this->getPaymentMethod() === PaymentMethod::CARD_IN_DELIVERY;
    }

    public function isOnlinePayment(): bool
    {
        return $this->getPaymentMethod() === PaymentMethod::ONLINE_PAYMENT || $this->isBlikPayment();
    }

    public function isBlikPayment(): bool
    {
        return $this->getPaymentMethod() === PaymentMethod::BLIK;
    }

    public function overallSum(): float
    {
        $sum = 0;

        /** @var CartItem $item */
        foreach ($this->items as $item) {
            $sum += $item->getSubtotal();
        }

        return round($sum, 2);
    }

    public function finalSumToPay(): float
    {
        return round($this->overallSum() + $this->deliveryCost() + $this->tip() - $this->discountAmount(), 2);
    }

    public function discountAmount(): float
    {
        return $this->promoCode ? round($this->promoCode->getDiscountAmount(), 2) : 0;
    }

    public function deliveryCost(): float
    {
        if ($this->delivery->isOwnPickup() || $this->delivery->isTableOrder() || $this->delivery->isDineIn()) {
            return 0;
        }

        if ($this->promoCode && $this->promoCode->hasFreeDeliveryCode()) {
            return 0;
        }

        if ($this->delivery->isFreeDeliveryForMinOrder($this->overallSum())) {
            return 0;
        }

        return round($this->delivery->cost(), 2);
    }

    public function minOrderRealized(): bool
    {
        return $this->overallSum() >= $this->minOrder();
    }

    public function minOrder(): float
    {
        $zone = $this->getZone();

        return $zone ? $zone->min_order_amount : 0;
    }

    public function tip(): float
    {
        return $this->tip ?? 0;
    }

    public function blikCode(): ?string
    {
        return (string)$this->blikCode;
    }

    public function getZone(): ?DeliveryZone
    {
        if (!isset($this->deliveryAddress()['latitude']) || !isset($this->deliveryAddress()['longitude'])) {
            return null;
        }

        return $this->delivery->getCorrectZone($this->restaurant, (float)$this->deliveryAddress()['latitude'], (float)$this->deliveryAddress()['longitude']);
    }

    public function deliveryAddress(): ?array
    {
        return $this->delivery->getAddress() ?? [];
    }

    public function deliveryType(): string
    {
        return $this->delivery->getType();
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getPersonalData(): array
    {
        return $this->personalData;
    }

    public function getRealizationTime(): ?Carbon
    {
        return $this->realizationTime;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getCartPromoCode(): CartPromoCode
    {
        return $this->promoCode;
    }

    public function wantsToSaveDeliveryAddress(): bool
    {
        return isset($this->deliveryAddress()['save_address']) && $this->deliveryAddress()['save_address'];
    }

    public function storeOrderToDB(?User $user): Order
    {
        $order = new Order([
            'status' => $this->isOnlinePayment() ? OrderStatus::WAITING_FOR_PAYMENT : OrderStatus::NEW,
            'delivery_type' => $this->deliveryType(),
            'delivery_cost' => $this->deliveryCost(),
            'final_sum_to_pay' => $this->finalSumToPay(),
            'overall_sum' => $this->overallSum(),
            'promo_code_discount' => $this->discountAmount(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_status' => PaymentStatus::NOT_PAID,
            'realized_on_time' => $this->getRealizationTime(),
            'delivery_address' => $this->deliveryAddress(),
            'personal_data' => $this->getPersonalData(),
            'comments' => $this->getComments(),
            'ip' => null,
            'source' => $this->source,
            'table_number' => $this->table,
            'tip' => $this->tip(),
            'email' => $user ? null : $this->getPersonalData()['email'],
            'onesignal_player_id' => $this->onesignal_player_id,
            'is_visible' => !$this->isOnlinePayment(),
        ]);
        $order->promoCode()->associate($this->getCartPromoCode()->getPromoCode());
        $order->restaurant()->associate($this->getRestaurant());
        $order->user()->associate($user);
        $order->save();

        $this->storeCartItems($this->getItems(), $order);

        //don't inform restaurant till order is not paid
        if (!$this->isOnlinePayment()) {
            event(new OrderChangedEvent($order, true));
        }

        return $order;
    }

    public function storeCartItems(Collection $items, Order $order)
    {
        /** @var CartItem $item */
        foreach ($items as $item) {
            $orderProduct = new OrderProduct([
                'quantity' => $item->getQuantity(),
                'name' =>  $item->getProduct()->name,
                'description' =>  $item->getProduct()->description,
                'subtotal' => $item->getSubtotal(),
            ]);

            $orderProduct->order()->associate($order);
            $orderProduct->product()->associate($item->getProduct());
            $orderProduct->save();


            /** @var CartItemAddonGroup $group */
            foreach ($item->getAddonGroups() as $group) {
                /** @var ProductAddonGroupAddon $addon */
                foreach ($group->getOptions() as $addon) {
                    $option = new OrderProductAddonGroup();
                    $option->orderProduct()->associate($orderProduct);
                    $option->addonGroup()->associate($group->getAddonGroup());
                    $option->addon()->associate($addon);
                    $option->addon_name = $addon->addon->name;
                    $option->addon_group_name = $group->getAddonGroup()->addonGroup->name;
                    $option->quantity = 1;

                    $option->save();
                }
            }
        }
    }

    public function getRestaurant(): Restaurant
    {
        return $this->restaurant;
    }
}
