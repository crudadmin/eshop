@if ( count($existingAdditionalFields) > 0 )
@component('mail::panel')
| {{ _('Ďalšie údaje') }} | |
| :------------- | ----------:|
@foreach($existingAdditionalFields as $fieldKey => $field)
| {{ @$field['name'] }}: | {{ $order->{$fieldKey} }} |
@endforeach
@endcomponent
@endif

@if ( $order->note )
@component('mail::panel')
<small><strong>{{ _('Poznámka') }}: </strong></small> {{ $order->note }}
@endcomponent
@endif