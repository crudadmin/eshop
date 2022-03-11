@if ( $delivery->description_email )
@component('mail::panel')
| {{ _('Podrobnosti k doprave') }} |
| :------------- |
| {!! $delivery->description_email !!} |
@endcomponent
@endif

@component('mail::panel')
| {{ $order->delivery_different || $order->deliveryPickupAddress ? _('Fakturačná adresa') : _('Fakturačná a dodacia adresa') }} | |
| :------------- | ----------:|
@if ( $order->is_company )
| {{ _('Firma') }}: | {{ $order->company_name }} |
| {{ _('IČ') }}: | {{ $order->company_id }} |
| {{ _('DIČ') }}: | {{ $order->company_tax_id }} |
| {{ _('IČ DPH') }}: | {{ $order->company_vat_id }} |
@endif
| {{ _('Meno a priezvisko') }}: | {{ $order->username }} |
@if ( $order->phone )
| {{ _('Telefón') }}: | {{ $order->phone }} |
@endif
| {{ _('Ulica') }}: | {{ $order->street }} |
| {{ _('Mesto') }}: | {{ $order->city }} |
| {{ _('PSČ') }}: | {{ $order->zipcode }} |
| {{ _('Krajina') }}: | {{ $order->country ? $order->country->name : '' }} |
@endcomponent

@if ( $order->delivery_different )
@component('mail::panel')
| {{ _('Dodacia adresa') }} | |
| :------------- | ----------:|
| {{ _('Meno a priezvisko / Firma') }}: | {{ $order->delivery_username }} |
| {{ _('Telefón') }}: | {{ $order->delivery_phone }} |
| {{ _('Ulica') }}: | {{ $order->delivery_street }} |
| {{ _('Mesto') }}: | {{ $order->delivery_city }} |
| {{ _('PSČ') }}: | {{ $order->delivery_zipcode }} |
| {{ _('Krajina') }}: | {{ $order->delivery_country ? $order->delivery_country->name : '' }} |
@endcomponent
@endif