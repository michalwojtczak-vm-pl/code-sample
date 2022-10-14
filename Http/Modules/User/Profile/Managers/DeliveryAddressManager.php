<?php declare(strict_types=1);


namespace App\Http\Modules\User\Profile\Managers;


use App\Http\Modules\User\Profile\Repositories\DeliveryAddressRepository;
use App\Http\Resources\User\UserDeliveryAddressResource;
use App\Models\ClientUser;
use App\Models\DeliveryAddress;

class DeliveryAddressManager
{
    /**
     * @var DeliveryAddressRepository
     */
    private $deliveryAddressRepository;

    public function __construct(
        DeliveryAddressRepository $deliveryAddressRepository
    )
    {
        $this->deliveryAddressRepository = $deliveryAddressRepository;
    }

    public function getAll()
    {
        return UserDeliveryAddressResource::collection(auth()->user()->deliveryAddresses);
    }

    public function update(DeliveryAddress $address, array $params)
    {
        foreach (auth()->user()->deliveryAddresses as $address) {
            $address->is_default = false;
            $address->save();
        }

        $address->update($params);

        return new UserDeliveryAddressResource($address);
    }

    public function add(array $params)
    {
        $address = new DeliveryAddress($params);
        $address->user()->associate(auth()->user());
        $address->save();

        return new UserDeliveryAddressResource($address);
    }

    public function remove(DeliveryAddress $address)
    {
        $address->delete();

        return true;
    }

    public function hasSameAddressSaved(ClientUser $user, array $params): bool
    {
        return $user->deliveryAddresses()->where([
            ['country', $params['country']],
            ['floor', $params['floor']],
            ['latitude', $params['latitude']],
            ['longitude', $params['longitude']],
            ['street_and_number', $params['street_and_number']],
            ['city', $params['city']],
            ['apartment_number', $params['apartment_number']],
            ['post_code', $params['post_code']],
        ])->count() > 0;
    }

    public function addAddress(ClientUser $user, array $params)
    {
        if ($this->hasSameAddressSaved($user, $params)) {
            return;
        }

        $address = new DeliveryAddress($params);
        $address->is_default = true;
        $address->user()->associate($user);
        $address->save();

    }
}
