<?php declare(strict_types=1);


namespace App\Http\Modules\Orders\Classes;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderChangedEvent;
use App\Models\ClientUser;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class POSCart extends Cart
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

    /** @var array */
    protected $personalData;

    /** @var Carbon */
    protected $realizationTime;

    /** @var Restaurant */
    protected $restaurant;
    /**
     * @var ClientUser|null
     */
    private $clientUser;

    public function __construct(array $params, User $user, Restaurant $restaurant)
    {
        $this->items = collect([]);
        $this->parse($params);
        $this->clientUser = $user;
        $this->restaurant = $restaurant;
    }

    public function parse(array $params)
    {
        foreach ($params['items'] ?? [] as $item) {
            $this->add(new CartItem($item));
        }

        $this->delivery = new Delivery($params['deliveryType'] ?? null, $params['deliveryAddress'] ?? null, false);
        $this->paymentMethod = $params['paymentMethod'] ?? null;
    }

    public function storeOrderToDB(?User $user): Order
    {
        $order = new Order([
            'status' => OrderStatus::ACCEPTED,
            'delivery_type' => $this->deliveryType(),
            'delivery_cost' => 0,
            'final_sum_to_pay' => $this->finalSumToPay(),
            'overall_sum' => $this->overallSum(),
            'promo_code_discount' => 0,
            'payment_method' => $this->getPaymentMethod(),
            'payment_status' => PaymentStatus::PAID,
            'personal_data' => [],
            'source' => 'POS',
            'email' => $user->email
        ]);

        $order->restaurant()->associate($this->getRestaurant());
        $order->user()->associate($user);
        $order->save();

        $this->storeCartItems($this->getItems(), $order);

        event(new OrderChangedEvent($order, true));

        return $order;
    }
}
