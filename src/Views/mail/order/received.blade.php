@component('mail::message')
# {{ _('Dobrý deň') }},

@if ( isset($message) )
{{ $message }}
@endif

@include('admineshop::mail.order.slots.items')

@include('admineshop::mail.order.slots.received_before')

@include('admineshop::mail.order.slots.info')

@include('admineshop::mail.order.slots.delivery')

@include('admineshop::mail.order.slots.received_after')

@include('admineshop::mail.order.slots.additional_info')

@include('admineshop::mail.order.slots.action_button')

{{ _('S pozdravom') }},<br>
{{ config('app.name') }}
@endcomponent
