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

        add_action( 'parse_request', array( $this, 'parse_request' ) );

		// do not delete!
    	parent::__construct();
	}

    public function parse_request($query) {
        if (!empty($query->query_vars['page']) && $query->query_vars['page'] == 'etac_process_stripe_connect_request') {
            // coming home from stripe connect!
            $this->_finish_stripe_connect_account_connection($query);
        }
        return;
    }
    private function _finish_stripe_connect_account_connection($query) {
        $settings = get_option( 'gfp_stripe_settings' );
        if ( isset( $_GET[ 'code' ] ) ) {
            $code = $_GET[ 'code' ];

//            $state = explode( ' ', $_GET[ 'state' ] );
//            $user  = get_user_by( 'id', $state[ 0 ] );
//            if ( ! $user ) {
//                GFP_Stripe::log_error( 'User ID ' . $state[ 0 ] . ' does not exist.' );
//
//                wp_redirect('etac_process_stripe_connect_error');# wp_redirect( $settings[ 'stripe_connect_redirect_error' ] );
//                exit;
//            }

            #$user_id = $user->ID;

            $access_token_url          = "https://connect.stripe.com/oauth/token";
            #$mode                      = empty( $state[ 1 ] ) ? false : $state[ 1 ];
            $access_token_request_body = array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_secret' => STRIPE_SECRET_KEY, # GFP_Stripe::get_api_key( 'secret', $mode )#'client_secret' => GFP_Stripe::get_api_key( 'secret', $mode )
            );
            $url                       = $access_token_url . '?' . http_build_query( $access_token_request_body );
            $access_token_request      = wp_remote_post( $url );
            $access_token_response     = json_decode( wp_remote_retrieve_body( $access_token_request ), true );

            if ( array_key_exists( 'error', $access_token_response ) ) {

                $error =  $access_token_response[ 'error' ] . '/' . $access_token_response[ 'error_description' ];#$error = $user_id . '/' . $access_token_response[ 'error' ] . '/' . $access_token_response[ 'error_description' ];
                #GFP_Stripe::log_error( $error );
                wp_redirect('/stripe_connect_failure');#wp_redirect( $settings[ 'stripe_connect_redirect_error' ] );
                exit;
            }

            $stripe_connect_info = array(
                'access_token'    => $access_token_response[ 'access_token' ],
                'publishable_key' => $access_token_response[ 'stripe_publishable_key' ],
                'account_id'      => $access_token_response[ 'stripe_user_id' ],
                'refresh_token'   => $access_token_response[ 'refresh_token' ]
            );
            // Ideally, this should be stored with the acf field group, but extra work and not known use case yet
            // we'll store just one for now
            update_option('options_etac_stripe_connect',$stripe_connect_info);
            #add_user_meta( $user_id, '_gfp_stripe_connect', $stripe_connect_info, true );
            #add_user_meta( $user_id, '_gfp_stripe_account_id', $access_token_response[ 'stripe_user_id' ], true );

            wp_redirect( 'stripe_connect_success' );#wp_redirect( $settings[ 'stripe_connect_redirect_success' ] );
            exit;
        } else if ( isset( $_GET[ 'error' ] ) ) {

            $error = $_GET[ 'state' ] . '/' . $_GET[ 'error' ] . '/' . $_GET[ 'error_description' ];
            GFP_Stripe::log_error( $error );
            wp_redirect('/stripe_connect_failure');#wp_redirect( $settings[ 'stripe_connect_redirect_error' ] );
            exit;
        }
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

        $stripeConnectButton = $this->generate_stripe_button($field);
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
    public function generate_stripe_button( $field) {
        $output = '';
        #$user_id = get_current_user_id();
        $allowUpdates = true;// <-- INPUT

        if ( !is_user_logged_in() ) {
            $output .= "You must be logged in to view this content.";
            #return '';
        } else if (! current_user_can('etac_be_cfo')) {
            #return '';
            $output .= "You must have CFO like capabilities to view this field.";

        } else {
            #global $stripe_connect;
            #$allowUpdates = (isset($attr['allow_updates']) && ($attr['allow_updates'] == 'true' || $attr['allow_updates'] == 1)) ? true : false;
            $firstTime = false;


            if ( empty(get_option('options_etac_stripe_connect') ) ) {
                $firstTime = true;
            }

            if ($firstTime || $allowUpdates) {
                $url = $this->build_stripe_connect_url(  );
                #print "<br>".__FILE__.__LINE__.__METHOD__."<br> $url";

                #wp_enqueue_style( 'gfp_stripe_connect_button', trailingslashit( GFP_STRIPE_CONNECT_URL ) . 'includes/button.css' );
                wp_enqueue_style( 'jStripeConnectButton', plugin_dir_url(__FILE__). '/css/jStripeConnectButton.css' );
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

    // only here because is private in GFP_Stripe_Connect
    // FYI: url and button docs: https://stripe.com/docs/connect/standalone-accounts
    // https://connect.stripe.com/oauth/authorize?response_type=code&client_id=ca_3tYprjqNoe2xqJAhuqa498MiZuVGXyIG&scope=read_write
    private function build_stripe_connect_url( ) {
        $authorize_url          = "https://connect.stripe.com/oauth/authorize";
        $client_id              = STRIPE_CONNECT_CLIENT_ID; //<-- INPUT, -or- defined in wp-config.php (most likely)
        $authorize_request_body = array(
            'client_id'     => $client_id,
            'response_type' => 'code',
            'scope'         => 'read_write', // FYI: if read, then we'can't make changes, like refunds, I think.
        );
        $url = $authorize_url . '?' . http_build_query( $authorize_request_body );
        return $url;
    }

}

new acf_field_et_stripe_connect();

endif;


