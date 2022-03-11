@component('mail::panel')
| {{ _('Informácie o objednávke') }} | |
| :------------- | ----------:|
| {{ _('Spôsob platby') }}: | {{ $payment_method->name }} |
| {{ _('Doprava') }}: | {{ $delivery->name }} {{ $order->deliveryPickupName ? '('.$order->deliveryPickupName.')' : '' }} |
@if ( $order->deliveryPickupAddress )
| {{ _('Odberné miesto') }}: | {{ $order->deliveryPickupAddress }} |
@endif
| {{ _('Tel. číslo') }}: | {{ $order->phone ?: $order->delivery_phone }} |
| {{ _('Vytvorená dňa') }}:  | {{ $order->created_at->format('d.m.Y H:i') }} |
@endcomponent