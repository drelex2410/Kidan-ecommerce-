<?php

namespace App\Services\Account;

use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddressBookService
{
    public function listForUser(User $user): Collection
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function create(User $user, array $payload): Address
    {
        return DB::transaction(function () use ($user, $payload) {
            $shippingCount = Address::query()->where('user_id', $user->id)->where('default_shipping', 1)->count();
            $billingCount = Address::query()->where('user_id', $user->id)->where('default_billing', 1)->count();

            $address = new Address();
            $address->user_id = $user->id;
            $this->fillAddress($address, $payload);
            $address->default_shipping = $shippingCount > 0 ? 0 : 1;
            $address->default_billing = $billingCount > 0 ? 0 : 1;
            if (Schema::hasColumn('addresses', 'set_default')) {
                $address->set_default = ($address->default_shipping || $address->default_billing) ? 1 : 0;
            }
            $address->save();

            return $address;
        });
    }

    public function update(User $user, array $payload): Collection
    {
        $address = Address::query()->findOrFail($payload['id']);
        $this->guardOwnership($user, $address);

        $this->fillAddress($address, $payload);
        $address->save();

        return $this->listForUser($user);
    }

    public function delete(User $user, int $addressId): Collection
    {
        $address = Address::query()->findOrFail($addressId);
        $this->guardOwnership($user, $address);

        $remaining = Address::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $address->id)
            ->latest()
            ->get();

        $nextDefault = $remaining->first();
        $wasDefaultShipping = (bool) $address->default_shipping;
        $wasDefaultBilling = (bool) $address->default_billing;

        $address->delete();

        if ($nextDefault) {
            if ($wasDefaultShipping && !$remaining->contains(fn ($item) => (bool) $item->default_shipping)) {
                $nextDefault->default_shipping = 1;
            }

            if ($wasDefaultBilling && !$remaining->contains(fn ($item) => (bool) $item->default_billing)) {
                $nextDefault->default_billing = 1;
            }

            if (Schema::hasColumn('addresses', 'set_default')) {
                $nextDefault->set_default = ($nextDefault->default_shipping || $nextDefault->default_billing) ? 1 : 0;
            }

            $nextDefault->save();
        }

        return $this->listForUser($user);
    }

    public function markDefaultShipping(User $user, int $addressId): Collection
    {
        $address = Address::query()->findOrFail($addressId);
        $this->guardOwnership($user, $address);

        Address::query()->where('user_id', $user->id)->update(['default_shipping' => 0]);
        $update = ['default_shipping' => 1];
        if (Schema::hasColumn('addresses', 'set_default')) {
            $update['set_default'] = 1;
        }
        Address::query()->whereKey($address->id)->update($update);

        return $this->listForUser($user);
    }

    public function markDefaultBilling(User $user, int $addressId): Collection
    {
        $address = Address::query()->findOrFail($addressId);
        $this->guardOwnership($user, $address);

        Address::query()->where('user_id', $user->id)->update(['default_billing' => 0]);
        $update = ['default_billing' => 1];
        if (Schema::hasColumn('addresses', 'set_default')) {
            $update['set_default'] = 1;
        }
        Address::query()->whereKey($address->id)->update($update);

        return $this->listForUser($user);
    }

    private function fillAddress(Address $address, array $payload): void
    {
        $country = Country::query()->findOrFail($payload['country']);
        $state = State::query()->findOrFail($payload['state']);
        $city = City::query()->findOrFail($payload['city']);

        $address->address = $payload['address'];
        $address->country = $country->name;
        $address->country_id = $country->id;
        $address->state = $state->name;
        $address->state_id = $state->id;
        $address->city = $city->name;
        $address->city_id = $city->id;
        $address->postal_code = $payload['postal_code'];
        $address->phone = $payload['phone'];
    }

    private function guardOwnership(User $user, Address $address): void
    {
        if ((int) $address->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException();
        }
    }
}
