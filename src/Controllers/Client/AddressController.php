<?php

namespace AdminEshop\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Admin;

class AddressController extends Controller
{
    public function get()
    {
        return [
            'addresses' => client()->addresses,
        ];
    }

    public function store()
    {
        $client = client();

        $validator = Admin::getModel('ClientsAddress')->getAddressValidator()->validate();

        $client->addresses()->create($validator->getData());

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne pridaná.'));
    }

    public function update()
    {
        $client = client();

        $address = $client->addresses()->findOrFail(request('id'));

        $validator = $address->getAddressValidator()->validate();

        $address->update($validator->getData());

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne uložená.'));
    }

    public function delete()
    {
        $client = client();

        $address = $client->addresses()->findOrFail(request('id'));
        $address->delete();

        return autoAjax()->data([
            'addresses' => $client->addresses
        ])->save(_('Adresa bola úspešne zmazaná.'));
    }

    public function setDefault($id)
    {
        client()->addresses()->findOrFail($id)->update([ 'default' => true ]);

        client()->addresses()->where('id', '!=', $id)->update([ 'default' => false ]);

        return autoAjax()->data([
            'addresses' => client()->addresses
        ]);
    }
}