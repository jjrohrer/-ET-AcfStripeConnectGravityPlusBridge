<?php


if( ! class_exists('acf_field_et_stripe_connect') ) :

class acf_field_et_stripe_connect extends acf_field {
	
	
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*/
	
	function __construct() {
		
		// vars
		$this->name = 'et_stripe_connect';
		$this->label = __("Stripe Connect Button",'acf');
		$this->category = 'Et-Appchase';
		$this->defaults = array(
			'et_stripe_connect'	=> '',
			'hide_label' => 'no',
		);
		
		
		// do not delete!
    	parent::__construct();
	}

    /*
    *  load_field()
    *  jjr - more like getting the label
    */

    function load_field( $field )
    {
        global $post;

        if($field['hide_label'] == 'yes' && $post->post_type != 'acf-field-group') {
            $field['label'] = '';
            echo '<style>div[data-key="'.$field['key'].'"] .acf-label {display:none;}</style>';
        }

        return $field;
    }

    /*
    *  render_field()
    */


	function render_field( $field ) {
        #echo "(Your Stripe connect button should go here.)";
        $stringVal = $field['et_stripe_connect'];

        $stripeConnectButton = $this->shortcode_stripe_connect(['allow_updates'=>1]);
        echo "
        $stringVal
        <p>
        <p>
        {$stripeConnectButton}";
        return;

	}
	
	/*
	*  field_group_admin_head()
	*
	*/
	
	function field_group_admin_head() {
		?>
<style>
	.acf-field-list .field_type-et_stripe_connect tr[data-name="name"],
	.acf-field-list .field_type-et_stripe_connect tr[data-name="instructions"],
	.acf-field-list .field_type-et_stripe_connect tr[data-name="required"] { display: none; }
</style>
		<?php
	}

	/*
	*  field_group_admin_enqueue_scripts()
	*/
	
	function field_group_admin_enqueue_scripts() {
	
		
		$dir = plugin_dir_url( __FILE__ );
		
		// register & include JS
		wp_register_script( 'acf-input-enhanced_message', "{$dir}js/input.js", array(), false, true );
		wp_enqueue_script('acf-input-enhanced_message');
		
	}


	
	/*
	*  render_field_settings()
	*/
	
	function render_field_settings( $field ) {
		
		// Message
		acf_render_field_setting( $field, array(
			'label'			=> __('Instructions','acf'),
			'instructions'	=> __('Instructions to user.','acf'),
			'type'			=> 'textarea',
			'name'			=> 'et_stripe_connect',
		));

		// Hide Label?
		acf_render_field_setting( $field, array(
			'label'			=> __('Hide Label','acf'),
			'type'			=> 'radio',
			'name'			=> 'hide_label',
			'layout'		=> 'horizontal',
			'choices'	=>	array(
				'yes' => __('Yes'),
				'no' => __('No'),
			)
		));
		
	}

    /*
     *  Allowed Formats:
     *  [stripe-connect allow_updates=1]
     *  [stripe-connect allow_updates='true']
     *  [stripe-connect]
     *
     * if 'allow_updates' is is anything other than (1) or ('true'), then it defaults to 'false'
     * If 'allow_updates' is true, then the user receives a message indicating they have already registered, but gives them an option to switch accounts.
     *
     * History: Modified by JJ Rohrer (jj.gravityplus@ascendly.com) to add allow_updates. Motivation: I was making a settings page using Advanced Custom Fields.
     *
     * Q: Why is this here?
     * A: You may have noticed that this is nearly identical to the same function in  GFP_Stripe_Connect. Well,
     * we don't maintain those, and their members are private, so we've copied them out here.  This is not good, but I
     * think better than hacking the core GFP_Stripe_Connect file.
     *
     */
    public function shortcode_stripe_connect( $attr, $content = null ) {
        $output = '';
        $user_id = get_current_user_id();

        global $stripe_connect;
        if (isset($stripe_connect)) {
            //print "Goodness";
        } else {
            echo "This only works with Gravity + Stripe, with Stripe Connect.  If you have this plugin, please enable it, otherwise visit https://gravityplus.pro.";
        }


        if ( 0 === $user_id ) {
            $output .= "You must be logged in to view this content.";

        } else {
            $allowUpdates = (isset($attr['allow_updates']) && ($attr['allow_updates'] == 'true' || $attr['allow_updates'] == 1)) ? true : false;
            $firstTime = false;
            if ( ! $stripe_connect->get_stripe_connect_vendor_details( $user_id )  ) {
                $firstTime = true;
            }

            if ($firstTime || $allowUpdates) {
                $url = $this->build_stripe_connect_url( $user_id );
                wp_enqueue_style( 'gfp_stripe_connect_button', trailingslashit( GFP_STRIPE_CONNECT_URL ) . 'includes/button.css' );
                $htmlButton = '<a href="' . $url . '" class="stripe-connect"><span>Connect with Stripe</span></a>';;
                if ($firstTime) {
                    $output .= $htmlButton;
                } else {
                    $theDomId = "revealConnectButton".uniqid();
                    $output .=<<<EOD

                    You are already setup with Stripe Connect!
                    <span style='color:gray;font-size:85%'>
                    <br>Although very uncommon, you can switch to a new Stripe Connect account.
                    <a onclick='jQuery("#$theDomId").toggle();' >(click to reveal Stripe Connect re-registration button)</a>
                    </span>
                        <br>
                        <span id='$theDomId' style='display:none;'>
                        $htmlButton
                        </span>
EOD;
                }

            } else {
                // already set-up and no updating allowed
                $output .= "You're already setup with Stripe Connect.";
            }

        }

        return $output;
    }

    // ============= Exact Copies from GFP_Stripe_Connect -BEGIN- ======================================================
    // only here because is private in GFP_Stripe_Connect
    private function build_stripe_connect_url( $user_id, $mode = false ) {
        $authorize_url          = "https://connect.stripe.com/oauth/authorize";
        $client_id              = ( ! $mode ) ? self::get_client_id() : self::get_client_id( $mode );
        $state                  = ( ! $mode ) ? $user_id : $user_id . '+' . $mode;
        $authorize_request_body = array(
            'client_id'     => $client_id,
            'response_type' => 'code',
            'scope'         => 'read_write',
            'state'         => $state
        );
        if ( $mode ) {
            $url = add_query_arg( $authorize_request_body, $authorize_url );
        } else {
            $url = $authorize_url . '?' . http_build_query( $authorize_request_body );
        }

        return $url;
    }

    // only here because is private in GFP_Stripe_Connect
    static private function get_client_id( $mode = false ) {
        $settings = get_option( 'gfp_stripe_settings' );
        if ( ! $mode ) {
            $mode = rgar( $settings, 'mode' );
        }
        $key = $mode . '_client_id';

        return trim( esc_attr( rgar( $settings, $key ) ) );
    }
    // ============= Exact Copies from GFP_Stripe_Connect -END- ======================================================


}

new acf_field_et_stripe_connect();

endif;


