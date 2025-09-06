<style>
    :root {
        --primary-color: {{ $primaryColor = theme_option('primary_color', '#821F40') }};
        --primary-color-rgb: {{ implode(',', BaseHelper::hexToRgb($primaryColor)) }};
        --secondary-color: {{ theme_option('secondary_color', '#821f40') }};
        --footer-background-color: {{ theme_option('footer_background_color', '#fff') }};
        --footer-text-color: {{ theme_option('footer_text_color', '#010f1c') }};
        --footer-title-color: {{ theme_option('footer_title_color', '#010f1c') }};
        --footer-link-color: {{ theme_option('footer_link_color', '#010f1c') }};
        --footer-link-hover-color: {{ theme_option('footer_link_hover_color', '#0989ff') }};
        --footer-border-color: {{ theme_option('footer_border_color', '#e5e6e8') }};
    }
</style>
