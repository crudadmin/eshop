@if ( $owner == false && $postPaymentUrl = $order->getPostPaymentUrl() )
{{ _('Ak ste platbu nevykonali po vytvorení objednávky, môžete ju zaplatiť aj z tohto e-mailu.') }}

@component('mail::button', ['url' => $postPaymentUrl ])
{{ _('Zaplatiť platbu online') }}
@endcomponent
@else
@component('mail::button', ['url' => url('/')])
    {{ _('Pokračovať na eshop') }}
@endcomponent
@endif