@if ( $items )
@component('mail::table')
| {{ _('Objednaný tovar') }}       | {{ _('Množstvo') }}      | {{ $showNoVat ? ('<span style="display: inline-block; width: 90px">'._('Cena bez dph').'</span>') : '' }} | <span style="display: inline-block; width: 90px">{{ _('Cena s dph') }}</span> |
| :------------ |:-------------:| ----------:| ----------:|
@foreach( $items as $item )
| {!! $item->emailItemName() !!} | {{ $item->quantity }} | {{ $showNoVat ? Store::priceFormat($item->getItemModel()->priceWithoutVat * $item->quantity) : '' }} | {{ Store::priceFormat($item->getItemModel()->totalPriceWithVat($item->quantity)) }} |
@endforeach
| {{ $delivery->name }} | - | {{ $showNoVat ? Store::priceFormat($order->delivery_price) : '' }} | {{ Store::priceFormat($order->delivery_price_with_vat) }} |
| {{ $payment_method->name }} | - | {{ $showNoVat ? Store::priceFormat($order->payment_method_price) : '' }} | {{ Store::priceFormat($order->payment_method_price_with_vat) }} |
@foreach($discounts as $discount)
@if ( $discount->message && $discount->canShowInEmail() )
| {{ $discount->getName() }} | - |  | {{ is_array($discount->message) ? $discount->message['withVat'] : $discount->message }} |
@endif
@endforeach
| <strong><small>{{ _('Cena celkom') }}:</small></strong> | | {{ $showNoVat ? Store::priceFormat($summary['priceWithoutVat']) : '' }} | {{ Store::priceFormat($summary['priceWithVat']) }} |
@endcomponent
@endif