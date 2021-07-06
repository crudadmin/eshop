@component('mail::panel')
| {{ _('Informácie o objednávke') }} | |
| :------------- | ----------:|
@if ( $payment_method )
| {{ _('Spôsob platby') }}: | {{ $payment_method->name }} |
@endif
@if ( $delivery )
| {{ _('Doprava') }}: | {{ $delivery->name }} {{ $location ? '('.$location->{config('admineshop.delivery.multiple_locations.field_name')}.')' : '' }} |
@endif
@if ( $location && $location->address )
| {{ _('Adresa zvolenej dopravy') }}: | {{ $location->address }} |
@endif
| {{ _('Tel. číslo') }}: | {{ $order->phone ?: $order->delivery_phone }} |
| {{ _('Vytvorená dňa') }}:  | {{ $order->created_at->format('d.m.Y H:i') }} |
@endcomponent