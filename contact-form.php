<?php
/**
 * Plugin Name: Contact Form
 * Description: contact form to contact us or send message
 * Author: Mokeddem M. Amine
 * Version: 1.0.0
 * Text Domaine: contact-form
 */
if(!defined('ABSPATH')){
    echo 'what are you trying to do ?';
    exit;
}

class ContactForm{

    public function __construct(){
        // create custom post
        add_action('init',array($this,'create_contactForm_post'));
        // add assets (css and js ...)
        add_action('wp_enqueue_scripts',array($this,'load_assets'));
        // add Shortcode
        add_shortcode('contact-form',array($this,'load_shortcode'));
        // load js
        add_action('wp_footer',array($this,'load_scripts'));
        // Register REST API
        add_action('rest_api_init',array($this,'register_rest_api'));
    }

    public function create_contactForm_post(){
        $args = array(
            'public'                => true,
            'has_archive'           => true,
            'supports'              => array('title'),
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'menu_icon'             => 'dashicons-format-aside',
            'capability'            => 'manage_options',
            'labels'                => array(
                'name'          => 'Contact Form',
                'singular_name' => 'Contact Form Entry',
            ),
            
        );
        register_post_type('contact_form',$args);
    }

    public function load_assets(){

        wp_enqueue_style('contact-form-css',plugin_dir_url(__FILE__).'css/contact-form.css',array(),1,'all');

        wp_enqueue_script('contact-form-js',plugin_dir_url(__FILE__).'js/contact-form.js',array('jquery'),false,true);
    }

    public function load_shortcode(){
        ?>
            <div class="contact-form-from-mam bg-white p-2 rounded">
                <h2 class="my-3 text-capitalize text-center">send us an email</h2>
                <form id="contact-form-from-mam" class="bg-light px-3 py-5 rounded" style="max-width:100%;width:500px;margin:auto;">
                    <div class="form-group mb-4">
                        <input type="text" name="name" placeholder="Name" class="form-control">
                    </div>
                    <div class="form-group mb-4">
                        <input type="email" name="email" placeholder="Email" class="form-control">
                    </div>
                    <div class="form-group mb-4">
                        <input type="tel" name="phone" placeholder="Phone" class="form-control">
                    </div>
                    <div class="form-group mb-4">
                        <textarea name="message" class="form-control" placeholder="Type your message"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <input type="submit" value="Send Message" class="btn btn-success ">
                    </div>
                </form>
            </div>
        <?php
    }

    public function load_scripts(){
        ?>
            <script>
                var nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
                jQuery(document).ready(function($){
                    $('#contact-form-from-mam').submit(function(e){
                        e.preventDefault();
                        
                        var form = $(this).serialize();

                        $.ajax({
                            method:'POST',
                            url: '<?php echo get_rest_url(null,'contact-form/v1/send-email'); ?>',
                            headers:{
                                'X-WP-Nonce': nonce,
                            },
                            data:form,
                            success:function(data){
                                console.log(data);
                            },
                            error:function(err){
                                console.log(err);
                            }
                        })
                    })
                })
            </script>
        <?php

    }

    public function register_rest_api(){
        register_rest_route('contact-form/v1','send-email',array(
            'methods'    => 'POST',
            'callback'  => array($this,'handle_contact_form'),
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_contact_form($data){
        $headers    = $data->get_headers();
        $params     = $data->get_params();

        $nonce      = $headers['x_wp_nonce'][0];
        
        if(!wp_verify_nonce($nonce,'wp_rest')){
            return new WP_REST_Response('message not sent',422);
        }

        $post_id = wp_insert_post([
            'post_type'     => 'contact_form',
            'post_title'    => 'contact enquiry',
            'post_status'   => 'publish',
        ]);
        
        if($post_id){
            return new WP_REST_Response('Thank you for your email',200);
        }
    }

}

new ContactForm;