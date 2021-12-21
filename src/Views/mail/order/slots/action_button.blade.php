@if ( $owner == false && $postPaymentUrl = $order->getPostPaymentUrl() )
{{ _('Ak ste platbu nevykonali po vytvorení objednávky, môžete ju zaplatiť aj z tohto e-mailu.') }}

@component('mail::button', ['url' => $postPaymentUrl ])
{{ _('Zaplatiť platbu online') }}
@endcomponent
@else
@component('mail::button', ['url' => env('APP_NUXT_URL') ?: url('/')])
    {{ _('Pokračovať na eshop') }}
@endcomponent
@endif