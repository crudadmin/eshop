<?php

namespace AdminEshop\Controllers\Client;

use AdminEshop\Models\Clients\ClientsAddress;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    private function rules()
    {
        return [
            'type', 'name',
            'username', 'phone',
            'street', 'zipcode', 'city', 'country_id',
        ];
    }

    public function get()
    {
        return auth()->user()->addresses;
    }

    public function store()
    {
        $client = auth()->user();

        $validator = (new ClientsAddress)->validator()->only(
            $this->rules()
        )->validate();

        $client->addresses()->create($validator->getData());

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne pridaná.'));
    }

    public function update()
    {
        $client = auth()->user();

        $address = $client->addresses()->findOrFail(request('id'));

        $validator = $address->validator()->only(
            $this->rules()
        )->validate();

        $address->update($validator->getData());

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne uložená.'));
    }

    public function delete()
    {
        $client = auth()->user();

        $address = $client->addresses()->findOrFail(request('id'));
        $address->delete();

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne zmazaná.'));
    }
}