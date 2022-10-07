<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
  <channel>
    @foreach($items as $k => $item)
    <item>
      <id>{{ $item['id'] }}</id>
      <title>{{ $item['name'] }}</title>
      <description>{{ $item['description'] }}</description>
      <availability>{{ $item['availability'] }}</availability>
      <condition>{{ $item['condition'] }}</condition>
      <price>{{ number_format($item['priceWithVat'], 2, '.', '') }} EUR</price>
      <link>{{ $item['feed_url'] }}</link>
      <image_link>{{ $item['feed_thumbnail'] }}</image_link>
      <brand>{{ $item['brand'] ?? null }}</brand>
      <quantity_to_sell_on_facebook>{{ $item['quantity'] }}</quantity_to_sell_on_facebook>
      <google_product_category>{{ $item['google_product_category'] ?? null }}</google_product_category>
      {{-- <sale_price>10,00 USD</sale_price> --}}
      <item_group_id>{{ $item['item_group_id'] }}</item_group_id>
      <gender>{{ $item['gender'] ?? null }}</gender>
      <color>{{ $item['color'] ?? null }}</color>
      <size>{{ $item['size'] ?? null }}</size>
      <age_group>{{ $item['age_group'] ?? null }}</age_group>
      <material>{{ $item['material'] ?? null }}</material>
      <pattern>{{ $item['pattern'] ?? null }}</pattern>
    </item>
    @endforeach
  </channel>
</rss>