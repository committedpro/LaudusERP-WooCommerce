jQuery(document).ready(function() {
    if (jQuery('.woocommerce-orders-table').find('a').length > 0) {
        jQuery('.woocommerce-orders-table').find('a.invoice').each(function(){
            jQuery(this).attr('target', '_blank');
        });
    }
});