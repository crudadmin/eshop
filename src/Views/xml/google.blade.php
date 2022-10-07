<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
  <channel>
    @foreach($items as $k => $item)
    <item>
      <title>{{ $item['name'] }}</title>
      <link>{{ $item['feed_url'] }}</link>
      <description>{{ $item['description'] }}</description>

      <g:id>{{ $item['id'] }}</g:id>
      <g:item_group_id>{{ $item['item_group_id'] }}</g:item_group_id>
      <g:availability>{{ $item['availability'] }}</g:availability>
      <g:price>{{ number_format($item['priceWithVat'], 2, '.', '') }} EUR</g:price>
      <g:image_link>{{ $item['feed_thumbnail'] }}</g:image_link>
      <g:brand>{{ $item['brand'] ?? null }}</g:brand>
      <g:gtin>{{ $item['ean'] ?? null }}</g:gtin>
      {{-- <g:mpn>64286482</g:mpn> --}}
      <g:update_type>merge</g:update_type>
      <g:google_product_category>{{ $item['google_product_category'] ?? null }}</g:google_product_category>
      <g:color>{{ $item['color'] ?? null }}</g:color>
      <g:gender>{{ $item['gender'] ?? null }}</g:gender>
      <g:size>{{ $item['size'] ?? null }}</g:size>
      <g:material>{{ $item['material'] ?? null }}</g:material>
      <g:pattern>{{ $item['pattern'] ?? null }}</g:pattern>
    </item>
    @endforeach
  </channel>
</rss>
