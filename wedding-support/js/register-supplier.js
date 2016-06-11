'use strict';
var cropbox = function(options){
    if( document.querySelector(options.imageBox) ) {
        var el = document.querySelector(options.imageBox),
            obj =
            {
                state: {},
                ratio: 1,
                options: options,
                imageBox: el,
                thumbBox: el.querySelector(options.thumbBox),
                spinner: el.querySelector(options.spinner),
                image: new Image(),
                getDataURL: function () {
                    var width = this.thumbBox.clientWidth,
                        height = this.thumbBox.clientHeight,
                        canvas = document.createElement("canvas"),
                        dim = el.style.backgroundPosition.split(' '),
                        size = el.style.backgroundSize.split(' '),
                        dx = parseInt(dim[0]) - el.clientWidth / 2 + width / 2,
                        dy = parseInt(dim[1]) - el.clientHeight / 2 + height / 2,
                        dw = parseInt(size[0]),
                        dh = parseInt(size[1]),
                        sh = parseInt(this.image.height),
                        sw = parseInt(this.image.width);
                    canvas.width = width;
                    canvas.height = height;
                    var context = canvas.getContext("2d");
                    context.drawImage(this.image, 0, 0, sw, sh, dx, dy, dw, dh);
                    var imageData = canvas.toDataURL('image/png');
                    return imageData;
                },
                getBlob: function () {
                    var imageData = this.getDataURL();
                    var b64 = imageData.replace('data:image/png;base64,', '');
                    var binary = atob(b64);
                    var array = [];
                    for (var i = 0; i < binary.length; i++) {
                        array.push(binary.charCodeAt(i));
                    }
                    return new Blob([new Uint8Array(array)], {type: 'image/png'});
                },
                zoomIn: function () {
                    this.ratio *= 1.1;
                    setBackground();
                },
                zoomOut: function () {
                    this.ratio *= 0.9;
                    setBackground();
                }
            },
            attachEvent = function (node, event, cb) {
                if (node.attachEvent)
                    node.attachEvent('on' + event, cb);
                else if (node.addEventListener)
                    node.addEventListener(event, cb);
            },
            detachEvent = function (node, event, cb) {
                if (node.detachEvent) {
                    node.detachEvent('on' + event, cb);
                }
                else if (node.removeEventListener) {
                    node.removeEventListener(event, render);
                }
            },
            stopEvent = function (e) {
                if (window.event) e.cancelBubble = true;
                else e.stopImmediatePropagation();
            },
            setBackground = function () {
                var w = parseInt(obj.image.width) * obj.ratio;
                var h = parseInt(obj.image.height) * obj.ratio;

                var pw = (el.clientWidth - w) / 2;
                var ph = (el.clientHeight - h) / 2;

                el.setAttribute('style',
                    'background-image: url(' + obj.image.src + '); ' +
                    'background-size: ' + w + 'px ' + h + 'px; ' +
                    'background-position: ' + pw + 'px ' + ph + 'px; ' +
                    'background-repeat: no-repeat');
            },
            imgMouseDown = function (e) {
                stopEvent(e);

                obj.state.dragable = true;
                obj.state.mouseX = e.clientX;
                obj.state.mouseY = e.clientY;
            },
            imgMouseMove = function (e) {
                stopEvent(e);

                if (obj.state.dragable) {
                    var x = e.clientX - obj.state.mouseX;
                    var y = e.clientY - obj.state.mouseY;

                    var bg = el.style.backgroundPosition.split(' ');

                    var bgX = x + parseInt(bg[0]);
                    var bgY = y + parseInt(bg[1]);

                    el.style.backgroundPosition = bgX + 'px ' + bgY + 'px';

                    obj.state.mouseX = e.clientX;
                    obj.state.mouseY = e.clientY;
                }
            },
            imgMouseUp = function (e) {
                stopEvent(e);
                obj.state.dragable = false;
            },
            zoomImage = function (e) {
                var evt = window.event || e;
                var delta = evt.detail ? evt.detail * (-120) : evt.wheelDelta;
                delta > -120 ? obj.ratio *= 1.1 : obj.ratio *= 0.9;
                setBackground();
            }

        obj.spinner.style.display = 'block';
        obj.image.onload = function () {
            obj.spinner.style.display = 'none';
            setBackground();

            attachEvent(el, 'mousedown', imgMouseDown);
            attachEvent(el, 'mousemove', imgMouseMove);
            attachEvent(document.body, 'mouseup', imgMouseUp);
            var mousewheel = (/Firefox/i.test(navigator.userAgent)) ? 'DOMMouseScroll' : 'mousewheel';
            attachEvent(el, mousewheel, zoomImage);
        };
        obj.image.src = options.imgSrc;
        attachEvent(el, 'DOMNodeRemoved', function () {
            detachEvent(document.body, 'DOMNodeRemoved', imgMouseUp)
        });

        return obj;
    }
};


jQuery( function( $ ) {
    $( document ).ready( function( e ) {
        $( ".register-supplier-btn" ).live('click', function(e) {
            e.preventDefault();

            var this_ele = $(this);
            var parent_div = this_ele.closest(".register-template");
            var parent_form = this_ele.closest(".supplier-register-form");

            var id = this_ele.data("id");

            var redirect = true;
            var supplier_pay = "free";
            var supplier_order_num = 0;
            var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;

            if( ! this_ele.hasClass("ws-back") ) {
                $(parent_div).find(".required").each(function () {
                    if ( $(this).val() == "" || $(this).val() == "---select---" ) {
                        $(this).closest(".field-block").find(".hugeit-error-message").html("Please Fill This Field");
                        redirect = false;
                    } else {
                        $(this).closest(".field-block").find(".hugeit-error-message").html("");
                    }
                })

                $(parent_div).find(".supplier-email").each(function () {
                    if ( ! testEmail.test( $(this).val() ) ) {
                        $(this).closest(".field-block").find(".hugeit-error-message").html("Please Fill Correct Email");
                        redirect = false;
                    }
                })
            }

            if( this_ele.hasClass( "during-purchase" ) ) {
                parent_form.find(".register-price-during-type").val(this_ele.data("type"));
            }

            if( redirect ) {
                $(".register-template").each(function () {
                    if ( $(this).data("id") == id ) {
                        $(this).removeClass("ws-hidden");
                    } else {
                        if ( ! $(this).hasClass("ws-hidden") ) {
                            $(this).addClass("ws-hidden");
                        }
                    }
                });

                if( this_ele.hasClass("register-complete-btn") ) {
                    var during_type = parent_form.find(".register-price-during-type").val();
                    if( during_type != "" && during_type != "free" ) {
                        var register_price_type = parent_form.find(".register-price-type").val();
                        var register_price_during_type = parent_form.find(".register-price-during-type").val();

                        if( register_price_type == "1" && register_price_during_type == "weekly" ) {
                            supplier_order_num = 9783;
                            //supplier_order_num = 9641;
                            supplier_pay = "weekly";
                        } else if( register_price_type == "1" && register_price_during_type == "monthly" ) {
                            supplier_order_num = 9789;
                            //supplier_order_num = 9643;
                            supplier_pay = "monthly";
                        }  else if( register_price_type == "1" && register_price_during_type == "annual" ) {
                            supplier_order_num = 9790;
                            //supplier_order_num = 9645;
                            supplier_pay = "annual";
                        } else if( register_price_type == "2" && register_price_during_type == "weekly" ) {
                            supplier_order_num = 9791;
                            //supplier_order_num = 9642;
                            supplier_pay = "weekly";
                        } else if( register_price_type == "2" && register_price_during_type == "monthly" ) {
                            supplier_order_num = 9792;
                            //supplier_order_num = 9644;
                            supplier_pay = "monthly";
                        } else if( register_price_type == "2" && register_price_during_type == "annual" ) {
                            supplier_order_num = 9793;
                            //supplier_order_num = 9690;
                            supplier_pay = "annual";
                        } else if( register_price_type == "3" && register_price_during_type == "weekly" ) {
                            supplier_order_num = 9794;
                            supplier_pay = "weekly";
                        } else if( register_price_type == "3" && register_price_during_type == "monthly" ) {
                            supplier_order_num = 9795;
                            supplier_pay = "monthly";
                        } else if( register_price_type == "3" && register_price_during_type == "annual" ) {
                            supplier_order_num = 9796;
                            supplier_pay = "annual";
                        } else {

                        }
                    }

                    var supplier_category = "";
                    $('.supplier-register-form .supplier-category').each(function(){
                        supplier_category += $(this).html() + "--";
                    });
                    var supplier_distance = $("#supplier_distance").val();
                    if( supplier_distance == "---select---" ) {
                        supplier_distance = 150;
                    }
                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'register_supplier',
                            supplier_business_name:   $("#supplier_business_name").val(),
                            supplier_company_name:    $("#supplier_company_name").val(),
                            supplier_website_address: $("#supplier_website_address").val(),
                            supplier_email_address:   $("#supplier_email_address").val(),
                            supplier_phone_number:    $("#supplier_phone_number").val(),
                            supplier_address:         $("#supplier_address").val(),
                            supplier_street:          $("#supplier_street").val(),
                            supplier_suburb:          $("#supplier_suburb").val(),
                            supplier_state:           $("#supplier_state").val(),
                            supplier_category:        supplier_category,
                            supplier_region:          $("#supplier_region").val(),
                            supplier_abn_number:      $("#supplier_abn_number").val(),
                            supplier_distance:        supplier_distance,
                            supplier_owner_name:      $("#supplier_owner_name").val(),
                            supplier_owner_email:     $("#supplier_owner_email").val(),
                            supplier_owner_phone:     $("#supplier_owner_phone").val(),
                            supplier_desc:            $("#supplier_desc").val(),
                            supplier_main_img:        $("#supplier_main_img").val(),
                            supplier_gallery0:        $("#supplier_gallery0").val(),
                            supplier_gallery1:        $("#supplier_gallery1").val(),
                            supplier_gallery2:        $("#supplier_gallery2").val(),
                            supplier_gallery3:        $("#supplier_gallery3").val(),
                            supplier_pay:             supplier_pay
                        }
                    }).done( function( data ) {
                        var registered_post_id = data;
                        if( supplier_order_num != "" && supplier_order_num != 0 ) {
                            $.ajax({
                                url: ajax_object.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'pay_supplier',
                                    supplier_order_num: supplier_order_num,
                                    registered_post_id: registered_post_id
                                }
                            }).done(function (data) {
                                window.location.replace(data);
                            })
                        } else {
                            $.ajax({
                                url: ajax_object.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'view_free_supplier',
                                    registered_post_id: registered_post_id
                                }
                            }).done(function (data) {
                                jQuery("#view_free_supplier").removeClass("ws-hidden");
                                $("#view_free_supplier").attr( "href", data );
                            })
                        }
                    })
                }
            }
        });

        $('#supplier_category_select').live('change', function() {
            var this_ele = $(this);
            var parent_div = this_ele.closest(".register-template");
            var parent_form = this_ele.closest(".supplier-register-form");

            var register_continue = true;
            parent_div.find(".textholder").val( this_ele.val() );

            parent_form.find(".want-wedding-supplier").each(function() { // avoid same category
                if( this_ele.val() == $(this).data('name') ) {
                    register_continue = false;
                }
            });

            if( register_continue ) {
                var price_type = this_ele.find(':selected').data('type');
                var register_price_type = parent_form.find(".register-price-type").val();
                var register_count = parent_form.find(".register-count").val();
                register_count ++;
                if ( register_count <= 3 ) {
                    parent_form.find(".register-count").val(register_count);
                    var type_field = parent_form.find(".register-type-table");
                    var type_field_val = type_field.html();

                    var new_type_field_val =
                        "<tr class='want-wedding-supplier' data-type = '" + price_type + "' data-name = '" + this.value + "'>" +
                        "<td class='supplier-category'>" +
                        this.value +
                        "</td>" +
                        "<td class='remove-supplier-category' data-name='" + this.value + "'>X</td>" +
                        "</tr>";

                    type_field.html( type_field_val + new_type_field_val );

                    if (register_price_type == "" || register_price_type < price_type) {
                        parent_form.find(".register-price-type").val(price_type);
                        if ( price_type == 1 ) {
                            var weekly_max_price = "$9.95 PER WEEK RECURRING PAYMENTS";
                            var monthly_max_price = "$39.95 PER MONTH RECURRING PAYMENTS";
                            var annual_max_price = "$395.00 PER +GST RECURRING PAYMENTS";
                        } else if ( price_type == 2 ) {
                            var weekly_max_price = "$11.95 PER WEEK RECURRING PAYMENTS";
                            var monthly_max_price = "$49.95 PER MONTH RECURRING PAYMENTS";
                            var annual_max_price = "$495.00 PER +GST RECURRING PAYMENTS";
                        } else if ( price_type == 3 ) {
                            var weekly_max_price = "$12.95 PER WEEK RECURRING PAYMENTS";
                            var monthly_max_price = "$59.95 PER MONTH RECURRING PAYMENTS";
                            var annual_max_price = "$595.00 PER +GST RECURRING PAYMENTS";
                        } else {

                        }
                        parent_form.find(".weekly-max-price").html( weekly_max_price );
                        parent_form.find(".monthly-max-price").html( monthly_max_price );
                        parent_form.find(".annual-max-price").html( annual_max_price );
                    }
                }
            }
        });

        $('.remove-supplier-category').live('click', function() {
            var this_ele = $(this);
            var parent_form = this_ele.closest(".supplier-register-form");
            var register_price_type = 0;

            this_ele.closest(".hugeit-contact-column-block").find(".want-wedding-supplier").each(function() {
                if( $(this).data("name") == this_ele.data("name") ) {
                    var register_count = this_ele.closest(".hugeit-contact-column-block").find(".register-count").val();
                    register_count --;
                    this_ele.closest(".hugeit-contact-column-block").find(".register-count").val(register_count);
                    $(this).remove();
                } else {
                    var price_type = $(this).data('type');
                    if( register_price_type < price_type ) {
                        register_price_type = price_type;
                    }
                }
            });

            parent_form.find(".register-price-type").val(register_price_type);

            if ( register_price_type == 1 ) {
                var weekly_max_price = "$9.95 PER WEEK RECURRING PAYMENTS";
                var monthly_max_price = "$39.95 PER MONTH RECURRING PAYMENTS";
                var annual_max_price = "$395.00 PER +GST RECURRING PAYMENTS";
            } else if ( register_price_type == 2) {
                var weekly_max_price = "$11.95 PER WEEK RECURRING PAYMENTS";
                var monthly_max_price = "$49.95 PER MONTH RECURRING PAYMENTS";
                var annual_max_price = "$495.00 PER +GST RECURRING PAYMENTS";
            } else if ( register_price_type == 3 ) {
                var weekly_max_price = "$12.95 PER WEEK RECURRING PAYMENTS";
                var monthly_max_price = "$59.95 PER MONTH RECURRING PAYMENTS";
                var annual_max_price = "$595.00 PER +GST RECURRING PAYMENTS";
            } else { //register_price_type == 0
                var weekly_max_price = "$** PER WEEK RECURRING PAYMENTS";
                var monthly_max_price = "$** PER MONTH RECURRING PAYMENTS";
                var annual_max_price = "$** PER +GST RECURRING PAYMENTS";
            }

            parent_form.find(".weekly-max-price").html(weekly_max_price);
            parent_form.find(".monthly-max-price").html(monthly_max_price);
            parent_form.find(".annual-max-price").html(annual_max_price);
        });

        $('#supplier_distance').live('change', function() {
            $( this ).closest(".field-block").find(".textholder").val( this.value );
        });

        $( ".ws-progress-bar" ).progressbar({
            value: false
        });

        var options =
        {
            imageBox: '.imageBox',
            thumbBox: '.thumbBox',
            spinner: '.spinner',
            imgSrc: ''
        }

        var cropper = new cropbox(options);

        $('.upload-file').live('click', function(e) {
            $(".crop-container").removeClass("ws-hidden").addClass("ws-hidden");
            $('.cropped').html('');
        });

        $('.upload-file').live('change', function(e) {
            var image_type = $(this).data("image_type");

            $(this).closest('#register_supplier_div').find('.crop-image-type').val( image_type );
            var reader = new FileReader();
            reader.onload = function(e) {
                options.imgSrc = e.target.result;
                cropper = new cropbox(options);
            }
            reader.readAsDataURL(this.files[0]);

            $(".crop-container").removeClass("ws-hidden");
        });

        $('#btnCrop').live('click', function(e) {
            var img = cropper.getDataURL();
            $('.cropped').html('<img src="'+img+'">');
            $('.register-supplier-btn').addClass("ws-hidden");
            $('.crop-btn-grp').addClass("ws-hidden");
            $('.crop-action').addClass("ws-hidden");
            $('.crop-waiting').removeClass("ws-hidden");
            var this_ele = $(this);
            var parent_form = this_ele.closest(".supplier-register-form");
            var upload_container = this_ele.closest('.supplier-register-form').find('.ws-upload-container');
            var crop_image_type = this_ele.closest('#register_supplier_div').find('.crop-image-type').val();
            var real_ele = '';
            $(upload_container).each(function(e) {
                var cur_type = $(this).data("image_type");
                if( crop_image_type == cur_type ) {
                    real_ele = $(this).find('.ws-hidden');
                }
            });

            $.ajax({
                url: ajax_object.ajax_url,
                method: "POST",
                data: {
                    action       : 'upload_file',
                    format: "json",
                    phone_number : parent_form.find("#supplier_phone_number").val(),
                    abn_number   : parent_form.find("#supplier_abn_number").val(),
                    cropped_file : img
                }
            })
                .done(function( data ) {
                    //data = data.replace(/\"/g, "");
                    var data = jQuery.parseJSON(data);
                    console.log(data);
                    if( data.error_occur && data.error_occur != "" ) {
                        alert( 'Unfortunately, Image upload fail.' );
                    } else {
                        $(real_ele).val( data.file_url );
                        $('.register-supplier-btn').removeClass("ws-hidden");
                    }
                })
                .fail( function() {
                    alert( 'Unfortunately, Image upload fail. Please try again.' );
                })
                .always( function () {
                    $('.crop-waiting').addClass("ws-hidden");
                    $('.crop-action').removeClass("ws-hidden");
                    $('.crop-btn-grp').removeClass("ws-hidden");
                }
            );
        });

        $('#btnZoomIn').live('click', function(e) {
            cropper.zoomIn();
        });

        $('#btnZoomOut').live('click', function(e) {
            cropper.zoomOut();
        });
    });
});