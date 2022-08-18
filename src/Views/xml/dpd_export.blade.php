@php
$parcelTypes = [
    'guarantee' => 'PM2',
    'dpd10' => 'AM1',
    'dpd12' => 'AM12',
];
@endphp
<?xml version="1.0"?>
<Import>
    <Orders>
        @foreach($orders as $order)
        @php
            $provider = $order->getShippingProvider();
        @endphp

        @continue(!$provider)
        <Order>
            <customer_name>{{ $order->username }}</customer_name>
            <customer_name2>{{ $order->username }}</customer_name2>
            <customer_street>{{ $order->street }}</customer_street>
            <customer_street2/>
            <customer_zipcode>{{ $order->zipcode }}</customer_zipcode>
            <customer_city>{{ $order->city }}</customer_city>
            <customer_country_id>{{ $order->country?->code }}</customer_country_id>
            <customer_phone>{{ $order->delivery_phone ?: $order->phone }}</customer_phone>
            <customer_email>{{ $order->email }}</customer_email>
            <parcel_type>{{ $parcelTypes[$provider->getOption('type')] ?? 'D' }}</parcel_type>
            <customer_type>C</customer_type>
            <parcel_weight>{{ $provider->getOption('weight', 1) }}</parcel_weight>
            <parcel_order_number>{{ $order->number }}</parcel_order_number>
            <sms_preadvice>Y</sms_preadvice>
            <phone_number>{{ $order->phone }}</phone_number>
            <parcel_cod>{{ $order->payment_method && $order->payment_method->isCashDelivery() ? 'Y' : 'N' }}</parcel_cod>
            <parcel_cod_amount>{{ $order->price_vat }}</parcel_cod_amount>
            <parcel_cod_currency>EUR</parcel_cod_currency>
            <parcel_cod_variable_symbol>{{ $order->number }}</parcel_cod_variable_symbol>
            <parcel_cod_cardpay>Y</parcel_cod_cardpay>
            @if ( $order->delivery_location )
            <parcelshop_id>{{ (int)$order->delivery_location->identifier }}</parcelshop_id>
            @endif
        </Order>
        @endforeach
    </Orders>
</Import>
