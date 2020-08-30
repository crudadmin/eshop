@component('mail::message')
# {{ _('Dobrý deň') }},

@if ( isset($message) )
{{ $message }}
@endif

## {{ _('Objednaný tovar') }}
@component('mail::table')
| {{ _('Názov produktu') }}       | {{ _('Množstvo') }}      | {{ _('Cena bez dph') }} | {{ _('Cena s dph') }} |
| :------------ |:-------------:| ----------:| ----------:|
@foreach( $items as $item )
| {{ $item->product->name }} @if ( isset($item->variant) )<small>{{ $item->variant->name }}</small> @endif| {{ $item->quantity }} | {{ Store::priceFormat($item->getItemModel()->priceWithoutVat * $item->quantity) }} | {{ Store::priceFormat($item->getItemModel()->totalPriceWithVat($item->quantity)) }} |
@endforeach
| {{ $delivery->name }} | - | {{ Store::priceFormat($order->delivery_price) }} | {{ Store::priceFormat($order->delivery_price_with_vat) }} |
| {{ $payment_method->name }} | - | {{ Store::priceFormat($order->payment_method_price) }} | {{ Store::priceFormat($order->payment_method_price_with_vat) }} |
@foreach($discounts as $discount)
@if ( $discount->message && $discount->canShowInEmail() )
| {{ $discount->getName() }} | - |  | {{ is_array($discount->message) ? $discount->message['withVat'] : $discount->message }} |
@endif
@endforeach
| <strong><small>{{ _('Cena celkom') }}:</small></strong> | | {{ Store::priceFormat($summary['priceWithoutVat']) }} | {{ Store::priceFormat($summary['priceWithVat']) }} |
@endcomponent

@component('mail::panel')
| {{ _('Informácie o objednávke') }} | |
| :------------- | ----------:|
| {{ _('Spôsob platby') }}: | {{ $payment_method->name }} |
| {{ _('Doprava') }}: | {{ $delivery->name }} {{ $location ? '('.$location->name.')' : '' }} |
| {{ _('Tel. číslo') }}: | {{ $order->phone }} |
| {{ _('Vytvorená dňa') }}:  | {{ $order->created_at->format('d.m.Y H:i') }} |
@endcomponent

@component('mail::panel')
| {{ $order->delivery_different ? _('Fakturačná adresa') : _('Fakturačná a dodacia adresa') }} | |
| :------------- | ----------:|
@if ( $order->is_company )
| {{ _('Firma') }} | {{ $order->company_name }} |
| {{ _('IČ') }} | {{ $order->company_id }} |
| {{ _('DIČ') }} | {{ $order->company_tax_id }} |
| {{ _('IČ DPH') }} | {{ $order->company_vat_id }} |
@endif
| {{ _('Meno a priezvisko') }}: | {{ $order->username }} |
| {{ _('Telefón') }} : | {{ $order->phone }} |
| {{ _('Ulica') }} : | {{ $order->street }} |
| {{ _('Mesto') }} : | {{ $order->city }} |
| {{ _('PSČ') }} : | {{ $order->zipcode }} |
| {{ _('Krajina') }} : | {{ $order->country ? $order->country->name : '' }} |
@endcomponent

@if ( $order->delivery_different )
@component('mail::panel')
| {{ _('Dodacia adresa') }} | |
| :------------- | ----------:|
| {{ _('Meno a priezvisko / Firma') }}: | {{ $order->delivery_username }} |
| {{ _('Telefón') }} : | {{ $order->delivery_phone }} |
| {{ _('Ulica') }} : | {{ $order->delivery_street }} |
| {{ _('Mesto') }} : | {{ $order->delivery_city }} |
| {{ _('PSČ') }} : | {{ $order->delivery_zipcode }} |
| {{ _('Krajina') }} : | {{ $order->delivery_country ? $order->delivery_country->name : '' }} |
@endcomponent
@endif

@if ( ($additionalFields = config('admineshop.cart.order.additional_email_fields', [])) && count($additionalFields) > 0 )
@component('mail::panel')
| {{ _('Ďalšie údaje') }} | |
| :------------- | ----------:|
@foreach($additionalFields as $fieldKey)
@continue(!($field = $order->getField($fieldKey)) || is_null($order->{$fieldKey}))
| {{ @$field['name'] }}: | {{ $order->{$fieldKey} }} |
@endforeach
@endcomponent
@endif

@if ( $order->note )
@component('mail::panel')
<small><strong>{{ _('Poznámka') }}: </strong></small> {{ $order->note }}
@endcomponent
@endif

@component('mail::button', ['url' => url('/')])
    {{ _('Pokračovať na eshop') }}
@endcomponent

{{ _('S pozdravom') }},<br>
{{ config('app.name') }}
@endcomponent
