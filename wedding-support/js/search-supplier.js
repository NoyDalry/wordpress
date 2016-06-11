jQuery( function( e ) {
    jQuery( document ).ready( function( e ){

        if ( jQuery( "#search_country" ).length ) {
            cookie_init();
        }

        function cookie_init() {
            var country = jQuery( "#search_country" ).val();
            var state = jQuery( "#search_state" ).val();
            var distance = jQuery( "#search_distance" ).val();
            var postcode = jQuery( "#search_postcode" ).val();

            setCookie( "cookie-country", country );
            setCookie( "cookie-state", state );
            setCookie( "cookie-distance", distance );
            setCookie( "cookie-postcode", postcode );
        }

        jQuery( "#supplier_search" ).click(function(e) {
            e.preventDefault();
            cookie_init();
        });

        function setCookie(cname, val) {
            var history = getCookie(cname);
            if( history != "" ) {
                document.cookie = cname + '=; expires=Thu, 2 Aug 2000 20:47:11 UTC; path=/;'
            }
            document.cookie = cname + '=' + val + '; expires=Thu, 2 Aug 2020 20:47:11 UTC; path=/;'
        }

        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return '';
        }

        jQuery( "img").on("load", function() {
            var this_ele = jQuery(".supplier-category-content");
            var supplier_type = jQuery(".supplier-category").data("type");

            var supplier_valids = [];
            var is_result = false;

            var search_country = getCookie("cookie-country");
            var search_state = getCookie("cookie-state");
            var search_distance = getCookie("cookie-distance");
            var search_postcode = getCookie("cookie-postcode");

            jQuery.ajax({
                url: ajax_object.ajax_url,
                type: "POST",
                data: {
                    action : 'check_supplier_valid',
                    supplier_type : supplier_type,
                    search_country : search_country,
                    search_state : search_state,
                    search_distance : search_distance,
                    search_postcode : search_postcode
                }
            }).done( function( data ) {
                var ret_obj = jQuery.parseJSON(data);
                this_ele.html(ret_obj);
            });
        });

        jQuery('.favorite-img').each(function() {
            var ID = jQuery(this).data("sup_id");
            var result = getCookie( "supplier-" + ID );
            if( result == 1 ) {
                jQuery(this).removeClass("unsaved-favorite").addClass("save-favorite");
            }
        });

        jQuery(".favorite-img").live('click', function(e) {
            var ID = jQuery(this).data("sup_id");
            var date = new Date();
            date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
            var expires = "; expires=" + date.toGMTString();

            var history = getCookie( "supplier-" + ID );
            if( history != "" ) {
                document.cookie = 'supplier-'  + ID + '=; expires=Thu, 2 Aug 2000 20:47:11 UTC; path=/;'
            }

            if( jQuery(this).hasClass("save-favorite") ) {
                jQuery(this).removeClass("save-favorite").addClass("unsaved-favorite");
                document.cookie = 'supplier-' + ID + '=0; expires=Thu, 2 Aug 2020 20:47:11 UTC; path=/;'
            } else {
                jQuery(this).removeClass("unsaved-favorite").addClass("save-favorite");
                document.cookie = 'supplier-' + ID + '=1; expires=Thu, 2 Aug 2020 20:47:11 UTC; path=/;'
            }
        });
    });
});
