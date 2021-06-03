@component('mail::message')
# {{ sprintf(_('Dobrý deň %s'), $order->firstname) }},

@if ( isset($message) )
{{ $message }}
@endif

@include('admineshop::mail.order.slots.invoice_button')

{{ _('S pozdravom') }},<br>
{{ config('app.name') }}
@endcomponent
