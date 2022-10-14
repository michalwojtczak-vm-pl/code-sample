<?php declare(strict_types=1);

namespace App\Http\Modules\Orders\Classes;

use App\Models\ProductAddonGroup;
use App\Models\ProductAddonGroupAddon;
use Illuminate\Support\Collection;

class CartItemAddonGroup
{
    /** @var ProductAddonGroup */
    protected $addonGroup;

    /** @var Collection */
    protected $options;
    /**
     * @var int
     */
    private $productId;

    public function __construct(array $params, int $productId)
    {
        $this->options = collect([]);
        $this->productId = $productId;
        $this->parse($params);
    }

    private function parse(array $params)
    {
        $this->setAddonGroup($params['id'] ?? null);

        foreach ($params['selection'] ?? [] as $id) {
            $this->addOption($id);
        }
    }

    public function getPrice(): float
    {
        $price = 0;

        /** @var  ProductAddonGroupAddon $addon */
        foreach ($this->options as $addon) {
            $price += $addon->getExtraPrice();
        }

        return $price;
    }

    public function setAddonGroup(?int $id)
    {
        $group = ProductAddonGroup::find($id);

        if (!$group || $group->product_id !== $this->productId) {
            throw new \InvalidArgumentException('Please supply a valid addon group ID.');
        }

        $this->addonGroup = $group;
    }

    private function addOption(?int $id)
    {
        $addon = ProductAddonGroupAddon::find($id);

        if (!$addon || $addon->product_addon_group_id !== $this->addonGroup->getKey()) {
            throw new \InvalidArgumentException('Please supply a valid addon ID.');
        }

        $this->options->add($addon);
    }

    public function getAddonGroup(): ProductAddonGroup
    {
        return $this->addonGroup;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }
}
