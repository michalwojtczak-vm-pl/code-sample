<?php

namespace App\Http\Modules\Orders\Classes;

use App\Enums\DeliveryType;
use App\Models\DeliveryZone;
use App\Models\Restaurant;

class Delivery
{
    /** @var string */
    protected $type;

    /** @var array */
    protected $address;
    /**
     * @var bool
     */
    private $errorIfIncorrect;
    /**
     * @var Restaurant|null
     */
    private $restaurant;

    public function __construct(?string $type, ?array $address, bool $errorIfIncorrect = true, ?Restaurant $restaurant = null)
    {
        $this->parse($type, $address);
        $this->errorIfIncorrect = $errorIfIncorrect;
        $this->restaurant = $restaurant;
    }

    private function parse(?string $type, ?array $address)
    {
        $this->setDeliveryType($type);
        $this->setAddress($address);
    }

    private function setDeliveryType(?string $type)
    {
        if ($this->errorIfIncorrect && (!$type || !in_array($type, DeliveryType::allTypes()))) {
            throw new \InvalidArgumentException('Please supply a valid delivery type');
        }

        $this->type = $type;
    }

    private function setAddress(?array $address)
    {
        if ($this->errorIfIncorrect && (!$this->isOwnPickup() && (!$address || !sizeof($address)))) {
            throw new \InvalidArgumentException('Please supply a valid delivery address');
        }

        $this->address = $address;
    }

    public function cost(): float
    {
        return $this->isOwnPickup() ? 0 : $this->calculateDeliveryCost();
    }

    public function isOwnPickup(): bool
    {
        return DeliveryType::isOwnPickup($this->type);
    }

    public function isTableOrder(): bool
    {
        return DeliveryType::isTableOrder($this->type);
    }

    public function isDineIn(): bool
    {
        return DeliveryType::isDineIn($this->type);
    }

    public function calculateDeliveryCost(): float
    {
        if (!$this->restaurant || sizeof($this->restaurant->deliveryZones) === 0) {
            return 0;
        }

        if (!$this->address['latitude'] || !$this->address['longitude']) {
            return 0;
        }

        $zone = $this->getCorrectZone($this->restaurant, $this->address['latitude'], $this->address['longitude']);

        return $zone ? $zone->delivery_cost : 0;
    }

    public function getCorrectZone(Restaurant $restaurant, ?float $lat, ?float $lang): ?DeliveryZone
    {
        if (!$lat || !$lang) {
            return null;
        }

        /** @var DeliveryZone $zone */
        foreach ($restaurant->deliveryZones as $zone) {
            if($zone->is_active && $zone->isCircle() && $this->checkIfInCircle($zone, $this->address['latitude'], $this->address['longitude'])) {
                return $zone;
            }
            if ($zone->is_active && $zone->isPolygon() && $this->checkIfInPolygon($zone, $this->address['latitude'], $this->address['longitude'])) {
                return $zone;
            }
        }

        return null;
    }

    public function canDeliver(Restaurant $restaurant, float $lat, float $lang): bool
    {
        return $this->getCorrectZone($restaurant, $lat, $lang) instanceof DeliveryZone;
    }

    public function isFreeDeliveryForMinOrder(float $orderAmount): bool
    {
        if (!$this->restaurant) {
            return false;
        }

        $zone = $this->getCorrectZone($this->restaurant, $this->address['latitude'], $this->address['longitude']);

        return $zone && $zone->min_order_for_free_delivery && $orderAmount >= $zone->min_order_for_free_delivery;
    }

    private function checkIfInPolygon(DeliveryZone $zone, float $lat, float $lng): bool
    {
        $polygon = [];

        foreach ($zone->points as $point) {
            $polygon[] = [$point['lat'], $point['lng']];
        }

        return $this->checkPolygon([$lat, $lng], $polygon);
    }

    public function checkPolygon($point, $polygon): bool
    {
        if($polygon[0] != $polygon[count($polygon)-1])
            $polygon[count($polygon)] = $polygon[0];
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);
        for ($i = 0; $i < $n; $i++)
        {
            $j++;
            if ($j == $n) {
                $j = 0;
            }
            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >= $y))) {
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] - $polygon[$i][1]) < $x) {
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
    }

    private function checkIfInCircle(DeliveryZone $zone, float $x, float $y): bool
    {
        return $this->distance($zone->getCircleX(), $zone->getCircleY(), $x, $y) <= $zone->radius;
    }

    /*
     * return distance in meters
     */
    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles * 1.609344 * 1000;
    }

    public function getAddress(): ?array
    {
        return $this->address;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
