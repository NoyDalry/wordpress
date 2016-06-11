<?php

// =============================================================================
// VIEWS/ETHOS/TEMPLATE-BLANK-1.PHP (Container | Header, Footer)
// -----------------------------------------------------------------------------
// A blank page for creating unique layouts.
// =============================================================================
global $wpdb;
?>
<?php get_header(); ?>
    <div class="x-container max width">
        <!--<div class="x-container max width main">-->
        <div class="offset cf">
            <div class="x-main full" role="main">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-wrap entry-content">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <div>
                                <div class="head_wood">
                                    <img src="/wp-content/uploads/2015/12/bride-suite_head.png" alt="" width="63%" height="380" />
                                </div>
                                <div class="wood_wordings">
                                    <h4><?php echo get_the_title(); ?></h4>
                                </div>
                                <label class="unsaved-favorite favorite-img" data-sup_id="<?php the_ID(); ?>"></label>
                            </div>
                            <div class="wood_head">
                                <?php
                                $post_id = get_the_ID();
                                $thumbnail = $wpdb->get_var("SELECT main_img FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $owner_name = $wpdb->get_var("SELECT owner_name FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $state = $wpdb->get_var("SELECT state FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $suburb = $wpdb->get_var("SELECT suburb FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $street = $wpdb->get_var("SELECT street FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $phone_number = $wpdb->get_var("SELECT phone_number FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                $website_address = $wpdb->get_var("SELECT website_address FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'");
                                ?>
                                <img src="<?php echo $thumbnail;  ?>" alt="" width="100%" height="380" />
                            </div>
                            <div class="wood_white">
                                <p><?php the_content(); ?></p>
                                <p><?php echo $owner_name; ?></p>
                            </div>
                            <div class="wood_conent">
                                <p><?php echo $street; ?></p>
                                <p><?php echo $state . "," . $suburb; ?></p>
                                <p><?php echo "PHONE " . $phone_number; ?></p>
                                <p><?php echo $website_address; ?></p>

                                <a href="#" data-enquire=" ">
                                    <img src="/wp-content/uploads/2015/12/brides_suite-3.png" alt="" width="100%" height="380" />
                                </a>
                            </div>

                            <script>
                                (function(x,u,i,p,t,z){x['__enquire_uuid__']=p;
                                    t=u.getElementsByTagName(i)[0];z=u.createElement(i);
                                    z.async=1;z.src='//assets.enquire.io/enquire.js';
                                    t.parentNode.insertBefore(z,t);
                                })(this,document,'script','dc1e2589-efe7-45ef-a055-1878f1a4e1de');
                            </script>
                            <?php x_link_pages(); ?>
                        <?php endwhile; ?>
                    </div>
                </article>
            </div>
        </div>
    </div>
<?php get_footer(); ?>