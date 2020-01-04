@component('mail::message')
# {{ _('Dobrý den') }},

{{ sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne přijatá.'), $order->number, $order->created_at->format('d.m.Y')) }}

## {{ _('Objednaný tovar') }}
@component('mail::table')
| {{ _('Názov produktu') }}       | {{ _('Množstvo') }}      | {{ _('Cena bez dph') }} | {{ _('Cena s dph') }} |
| :------------ |:-------------:| ----------:| ----------:|
@foreach( Basket::all() as $item )
| {{ $item->product->name }} @if ( $item->product->variant )<small> @foreach($item->product->variant->attributes as $attribute) {{ $attribute->attribute->name }}: {{ $attribute->item->name.$attribute->attribute->unit }}@if(!$loop->last),@endif @endforeach </small> @endif| {{ $item->quantity }} | {{ Basket::priceWithoutTax($item->product->priceWithoutTax * $item->quantity) }} | {{ Basket::price($item->product->priceWithTax * $item->quantity) }} |
@endforeach
| {{ $delivery->name }} | - | {{ Basket::priceWithoutTax($delivery->priceWithoutTax) }} | {{ Basket::price($delivery->priceWithTax) }} |
| {{ $payment_method->name }} | - | {{ Basket::priceWithoutTax($payment_method->priceWithoutTax) }} | {{ Basket::price($payment_method->priceWithTax) }} |
| <strong><small>{{ _('Cena celkem') }}:</small></strong> | | {{ Basket::getTotalBalance(true, false) . ' ' . Basket::getCurrency() }} | {{ Basket::getTotalBalance() . ' ' . Basket::getCurrency() }} |
@endcomponent

@component('mail::panel')
| {{ _('Informácie o objednávke') }} |
| :------------- | ----------:|
| {{ _('Spôsob platby') }}: | {{ $payment_method->name }} |
| {{ _('Doprava') }}: | {{ $delivery->name }} |
| {{ _('Tel. číslo') }}: | {{ $order->phone }} |
| {{ _('Vytvorená dňa') }}:  | {{ $order->created_at->format('d.m.Y H:i') }} |
@endcomponent

@component('mail::panel')
| {{ _('Fakturačná adresa') }} |
| :------------- | ----------:|
@if ( $order->isCompany() )
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
| {{ _('Krajina') }} : | {{ $order->country->name }} |
@endcomponent

@component('mail::panel')
| {{ _('Dodacia adresa') }} |
| :------------- | ----------:|
@if ( $order->delivery_company_name )
| {{ _('Firma') }}: | {{ $order->delivery_company_name }} |
@endif
| {{ _('Meno a priezvisko') }}: | {{ $order->delivery_username }} |
| {{ _('Telefón') }} : | {{ $order->delivery_phone }} |
| {{ _('Ulica') }} : | {{ $order->delivery_street }} |
| {{ _('Mesto') }} : | {{ $order->delivery_city }} |
| {{ _('PSČ') }} : | {{ $order->delivery_zipcode }} |
| {{ _('Krajina') }} : | {{ $order->delivery_country->name }} |
@endcomponent

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
