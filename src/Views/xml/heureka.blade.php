<?xml version="1.0" encoding="utf-8"?>
<SHOP>
  @foreach( $items as $product )
  <SHOPITEM>
    <ITEM_ID>{{ $product['id'] }}</ITEM_ID>
    <PRODUCTNAME>{{ $product['name'] }}</PRODUCTNAME>
    <PRODUCT>{{ $product['name'] }}</PRODUCT>
    <DESCRIPTION>{{ strip_tags(($product['description'] ?? null) ?: '') }}</DESCRIPTION>
    <URL>{{ $product['feed_url'] ?: $product['url'] ?? null }}</URL>
    <IMGURL>{{ $product['feed_thumbnail'] }}</IMGURL>
    {{-- <IMGURL_ALTERNATIVE>http://obchod.sk/mobily/nokia-5800-xpressmusic/obrazok2.jpg</IMGURL_ALTERNATIVE> --}}
    <PRICE_VAT>{{ $product['priceWithVat'] }}</PRICE_VAT>
    @if ( isset($product['video_url']) )
    <VIDEO_URL>{{ $product['video_url'] }}</VIDEO_URL>
    @endif
    @if ( isset($product['vat']) )
    <VAT>{{ $product['vat'] }}%</VAT>
    @endif
    {{-- <HEUREKA_CPC>0,24</HEUREKA_CPC> --}}
    @if ( $product['manufacturer'] ?? null )
    <MANUFACTURER>{{ $product['manufacturer'] }}</MANUFACTURER>
    @endif
    <CATEGORYTEXT>{{ implode(' | ', $product['heureka_category_list'] ?? []) }}</CATEGORYTEXT>
    @if ( isset($product['heureka_category_original_list']) )
    <WEBKATEGORIA>{{ implode(' | ', $product['heureka_category_original_list'] ?? []) }}</WEBKATEGORIA>
    @endif
    <EAN>{{ $product['ean'] ?? '' }}</EAN>
    <PRODUCTNO>{{ $product['code'] ?? '' }}</PRODUCTNO>
    {{-- <PARAM>
      <PARAM_NAME>Farba</PARAM_NAME>
      <VAL>čierna</VAL>
    </PARAM> --}}
    @if ( is_null($product['delivery_date'] ?? null) === false )
    <DELIVERY_DATE>{{ $product['delivery_date'] }}</DELIVERY_DATE>
    @endif
    @foreach( $deliveries as $delivery )
    <DELIVERY>
      <DELIVERY_ID>{{ $delivery->heureka_id }}</DELIVERY_ID>
      <DELIVERY_PRICE>{{ $delivery->priceWithVat }}</DELIVERY_PRICE>
      {{-- <DELIVERY_PRICE_COD>{{ $delivery->priceWithVat }}</DELIVERY_PRICE_COD> --}}
    </DELIVERY>
    @endforeach
    <ITEMGROUP_ID>{{ $product['heureka_item_id'] ?? '' }}</ITEMGROUP_ID>
    @foreach($product['attributes'] ?? [] as $attribute)
    <param>
        <param_name>{{ $attribute['name'] }}</param_name>
        <val>{{ $attribute['value'] }}</val>
    </param>
    @endforeach
    {!! $product['custom_xml'] ?? '' !!}
    {{-- <ACCESSORY>CD456</ACCESSORY> --}}
    {{-- <GIFT>Púzdro zadarmo</GIFT> --}}
    {{-- <EXTENDED_WARRANTY>
        <VAL>36</VAL>
        <DESC>Záruka na 36 mesiacov</DESC>
    </EXTENDED_WARRANTY> --}}
    {{-- <SPECIAL_SERVICE>Aplikácia ochrannej fólie</SPECIAL_SERVICE> --}}
  </SHOPITEM>
  @endforeach
</SHOP>