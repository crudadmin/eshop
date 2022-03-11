@component('mail::message')
# {{ sprintf(_('Dobrý deň %s'), $order->firstname) }},

@include('admineshop::mail.order.slots.status.slot_before')

@if ( $order->status->email_content )
{!! $order->status->email_content !!}
@endif

{{-- Delivery info --}}
@if ( $order->status->email_delivery && $order->delivery && $order->delivery->description_email_status )
{!! $order->delivery->description_email_status !!}
@endif

@include('admineshop::mail.order.slots.status.slot_after')

{{-- Delivery tracking button --}}
@if ( $order->deliveryTrackingUrl )
@component('mail::button', ['url' => $order->deliveryTrackingUrl])
    {{ _('Sledovať objednávku') }}
@endcomponent
@endif

@include('admineshop::mail.order.slots.footer')
@endcomponent
