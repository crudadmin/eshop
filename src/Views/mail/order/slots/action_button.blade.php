@if ( $owner == false )
{{-- Payment action --}}
@if ( $postPaymentUrl = $order->getPostPaymentUrl() )
{{ _('Ak ste platbu nevykonali po vytvorení objednávky, môžete ju zaplatiť aj z tohto e-mailu.') }}

@component('mail::button', ['url' => $postPaymentUrl ])
{{ _('Zaplatiť platbu online') }}
@endcomponent

{{-- TODO: delivery data are not available in this email yet --}}
@elseif ( $order->deliveryTrackingUrl )
@component('mail::button', ['url' => $order->deliveryTrackingUrl])
{{ _('Sledovať objednávku') }}
@endcomponent

{{-- No action --}}
@else
@component('mail::button', ['url' => env('APP_NUXT_URL') ?: url('/')])
{{ _('Pokračovať na eshop') }}
@endcomponent
@endif
@endif