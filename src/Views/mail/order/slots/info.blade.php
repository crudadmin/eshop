@component('mail::panel')
| {{ _('Informácie o objednávke') }} | |
| :------------- | ----------:|
| {{ _('Spôsob platby') }}: | {{ $payment_method->name }} |
| {{ _('Doprava') }}: | {{ $delivery->name }} {{ $location ? '('.$location->name.')' : '' }} |
@if ( $location && $location->address )
| {{ _('Adresa zvolenej dopravy') }}: | {{ $location->address }} |
@endif
| {{ _('Tel. číslo') }}: | {{ $order->phone ?: $order->delivery_phone }} |
| {{ _('Vytvorená dňa') }}:  | {{ $order->created_at->format('d.m.Y H:i') }} |
@endcomponent