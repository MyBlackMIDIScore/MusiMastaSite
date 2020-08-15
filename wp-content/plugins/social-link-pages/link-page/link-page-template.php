<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo esc_attr( $page_data->displayName ) ?></title>
    <meta name="description"
          content="<?php echo ! empty( $page_data->description ) ? esc_attr( $page_data->description ) : esc_attr( $page_data->displayName ) ?>"/>

    <meta property="og:title" content="<?php echo esc_attr( $page_data->displayName ) ?>"/>
    <meta property="og:site_name" content="<?php echo esc_attr( site_url() ) ?>"/>
    <meta property="og:description"
          content="<?php echo ! empty( $page_data->description ) ? esc_attr( $page_data->description ) : esc_attr( $page_data->displayName ) ?>"/>
    <meta property="og:type" content="profile"/>
	<?php if ( ! empty( $page_data->avatar ) ) : ?>
        <meta property="og:image" content="<?php echo esc_attr( $page_data->avatar ) ?>"/>
	<?php endif ?>
    <meta property="og:url" content="<?php echo esc_attr( site_url() ) . '/' . esc_attr( $page_data->slug ) ?>"/>

	<?php Social_Link_Pages_Page()->wp_head(); ?>

</head>

<body>

<div id="<?php echo Social_Link_Pages()->plugin_name_friendly ?>-root"></div>
<?php wp_nonce_field( $page_data->id, Social_Link_Pages()->plugin_name_friendly . '_wpnonce' ) ?>

<?php if ( ! empty( $page_data->GoogleAnalytics ) ) : ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async
            src="https://www.googletagmanager.com/gtag/js?id=<?php echo $page_data->GoogleAnalytics ?>>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', '<?php echo $page_data->GoogleAnalytics ?>');
    </script>
<?php endif ?>

<?php if ( ! empty( $page_data->FacebookPixel ) ) : ?>
    <!-- Facebook Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo $page_data->FacebookPixel ?>');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=<?php echo $page_data->FacebookPixel ?>&ev=PageView&noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
<?php endif ?>

<?php Social_Link_Pages_Page()->wp_footer(); ?>
</body>
</html>