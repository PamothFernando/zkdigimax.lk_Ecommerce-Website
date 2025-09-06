@php
    $style = in_array($shortcode->style, ['grid', 'slider', 'list']) ? $shortcode->style : 'grid';
@endphp

{!! Theme::partial("shortcodes.ecommerce-categories.$style", compact('shortcode', 'categories')) !!}
