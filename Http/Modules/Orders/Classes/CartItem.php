<?php declare(strict_types=1);

namespace App\Http\Modules\Orders\Classes;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartItem implements \JsonSerializable
{
    /** @var int */
    protected $quantity;

    /** @var Product */
    protected $product;

    /** @var Collection */
    protected $addonGroups;


    public function __construct(array $data)
    {
        $this->addonGroups = collect([]);
        $this->parse($data);
    }

    private function parse(array $params)
    {
        $this->setProduct(Product::fromRequestCompany()->find($params['id'] ?? null));
        $this->setQuantity($params['quantity'] ?? null);
        $this->setAddonGroups($params['addon_groups'] ?? []);
    }

    public function getSubtotal(): float
    {
        return $this->quantity * ($this->product->getPrice() + $this->getExtraPriceForAddons());
    }

    private function getExtraPriceForAddons(): float
    {
        $sum = 0;

        /** @var CartItemAddonGroup $group */
        foreach ($this->addonGroups as $group) {
            $sum += $group->getPrice();
        }

        return $sum;
    }

    public function setAddonGroups(array $params)
    {
        foreach ($params as $group) {
            $this->addonGroups->add(new CartItemAddonGroup($group, $this->product->getKey()));
        }
    }

    public function setProduct(?Product $product)
    {
        if (!$product) {
            throw new \InvalidArgumentException('Please supply a valid product ID.');
        }

        $this->product = $product;
    }

    public function setQuantity($qty)
    {
        if (!is_numeric($qty)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->quantity = $qty;
    }

    public function setPrice($qty)
    {
        if (!is_numeric($qty)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->quantity = $qty;
    }

    public function jsonSerialize()
    {
        return [
          'id' => $this->product->getKey(),
          'quantity' => $this->quantity,
          'addon_groups' => $this->addonGroups
        ];
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getAddonGroups(): Collection
    {
        return $this->addonGroups;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
