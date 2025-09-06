@php
    Theme::set('pageTitle', $category->name);
@endphp

<section class="tp-shop-area @if (! theme_option('theme_breadcrumb_enabled', true)) pt-50 @endif">
    <div class="container position-relative">
        {!! dynamic_sidebar('products_by_category_top_sidebar') !!}

        @include(Theme::getThemeNamespace('views.ecommerce.includes.products-listing'), ['pageName' => $category->name, 'pageDescription' => $category->description])

        {!! dynamic_sidebar('products_by_category_bottom_sidebar') !!}
    </div>
</section>
