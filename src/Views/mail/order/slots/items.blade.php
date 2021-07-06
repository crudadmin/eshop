@if ( $items )
@component('mail::table')
| {{ _('Objednaný tovar') }}       | {{ _('Množstvo') }}      | {{ $showNoVat ? ('<span style="display: inline-block; width: 90px">'._('Cena bez dph').'</span>') : '' }} | <span style="display: inline-block; width: 90px">{{ _('Cena s dph') }}</span> |
| :------------ |:-------------:| ----------:| ----------:|
@foreach( $items as $item )
| {!! $item->emailItemName() !!} | {{ $item->quantity }} | {{ $showNoVat ? Store::priceFormat($item->getItemModel()->priceWithoutVat * $item->quantity) : '' }} | {{ Store::priceFormat($item->getItemModel()->totalPriceWithVat($item->quantity)) }} |
@endforeach
@if ( $delivery )
| {{ $delivery->name }} | - | {{ $showNoVat ? Store::priceFormat($order->delivery_price) : '' }} | {{ Store::priceFormat($order->delivery_price_with_vat) }} |
@endif
@if ( $payment_method )
| {{ $payment_method->name }} | - | {{ $showNoVat ? Store::priceFormat($order->payment_method_price) : '' }} | {{ Store::priceFormat($order->payment_method_price_with_vat) }} |
@endif
@foreach($discounts as $discount)
@if ( $discount->messages && $discount->canShowInEmail() )
@foreach($discount->messages as $message)
| {{ $message['name'] }} | - |  | {{ is_array($message['value']) ? $message['value']['withVat'] : $message['value'] }} |
@endforeach
@endif
@endforeach
| <strong><small>{{ _('Cena celkom') }}:</small></strong> | | {{ $showNoVat ? Store::priceFormat($summary['priceWithoutVat']) : '' }} | {{ Store::priceFormat($summary['priceWithVat']) }} |
@endcomponent
@endif