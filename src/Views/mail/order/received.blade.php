@component('mail::message')
# {{ _('Dobrý deň') }},

@if ( isset($message) )
{!! $message !!}
@endif

@include('admineshop::mail.order.slots.items')

@include('admineshop::mail.order.slots.slot_before')

@include('admineshop::mail.order.slots.info')

@include('admineshop::mail.order.slots.delivery')

@include('admineshop::mail.order.slots.slot_middle')

@include('admineshop::mail.order.slots.additional_info')

@include('admineshop::mail.order.slots.slot_after')

@include('admineshop::mail.order.slots.action_button')

@include('admineshop::mail.order.slots.footer')
@endcomponent
