@component('mail::message')
# {{ sprintf(_('Dobrý deň %s'), $order->firstname) }},

{{-- Status info --}}
@if ( $order->status->email_content )
@component('mail::panel')
| {{ _('Informácia k stavu objednávky') }} |
| :------------- |
| {!! $order->status->email_content !!} |
@endcomponent
@endif

{{-- Delivery info --}}
@if ( $order->status->email_delivery && $order->delivery )
@component('mail::panel')
| {{ _('Informácie k doprave') }} |
| :------------- |
| {!! $order->delivery->description_email_status !!} |
@endcomponent
@endif

{{-- Delivery tracking button --}}
@if ( $order->deliveryTrackingUrl )
@component('mail::button', ['url' => $order->deliveryTrackingUrl])
    {{ _('Sledovať objednávku') }}
@endcomponent
@endif

{{ _('S pozdravom') }},<br>
{{ config('app.name') }}
@endcomponent
