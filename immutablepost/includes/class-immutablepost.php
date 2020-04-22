<?php
/**
 * Main plugin class file.
 *
 * @package ImmutablePost/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class ImmutablePost {

	/**
	 * The single instance of ImmutablePost.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of ImmutablePost_Admin_API
	 *
	 * @var ImmutablePost_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'immutablepost';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new ImmutablePost_Admin_API();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Register post type function.
	 *
	 * @param string $post_type Post Type.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param string $description Description.
	 * @param array  $options Options array.
	 *
	 * @return bool|string|ImmutablePost_Post_Type
	 */
	public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return false;
		}

		$post_type = new ImmutablePost_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param array  $post_types Post types to register this taxonomy for.
	 * @param array  $taxonomy_args Taxonomy arguments.
	 *
	 * @return bool|string|ImmutablePost_Taxonomy
	 */
	public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return false;
		}

		$taxonomy = new ImmutablePost_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles() {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_localize_script( $this->_token . '-frontend', 'my_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * 
	 * 
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'immutablepost', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'immutablepost';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main ImmutablePost Instance
	 *
	 * Ensures only one instance of ImmutablePost is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object ImmutablePost instance
	 * @see ImmutablePost()
	 * @since 1.0.0
	 * @static
	 */

  
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		
		function immutablepost_shortcode() {

			wp_enqueue_script( 'immutablepostjs', '/wp-content/plugins/immutablepost/assets/js/index.js' );
			//wp_register_script( $this->_token . '-immutablepostjs', esc_url( $this->assets_url ) . 'js/index.js', array( 'jquery' ), $this->_version, true );
		     //wp_enqueue_script( $this->_token . '-immutablepostjs' );
			// Get Ethereum Wallet Address
			$ether_address = get_option('wpt_text_field');
			// Get Form title
			$form_title = get_option('wpt_form_title');

			$form_company_title = get_option('wpt_company_title');
			$form_your_fullname = get_option('wpt_your_fullname');
			$form_your_address = get_option('wpt_your_address');
			$form_country = get_option('wpt_country');
			$form_your_phone = get_option('wpt_your_phone');
			$form_your_email = get_option('wpt_your_email');
			
			//Form to display

		    $form_post ='<div id="noweb3" style="display: none;">';
            $form_post .='<div class="bn-onboard-custom bn-onboard-icon-display svelte-18zts4b">';
            $form_post .='<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACYAAAAoCAMAAACl6XjsAAABfVBMVEVHcEx/Rh2ARx2ESiLwiCLMu67cfCagZDOddlJ+Rx7pgiPlfyPshSN/Rxx+Rh2CSB6ASB2JTR7gkkl1SCbQciPUwLLMu6+BSB5/Rx2DSR5+RxzefR3ogCPlfiPGuLHhfiP2jCTvhyN/Rx3nfyPfo2/lfCTIuK7kfyP2jCPkfiPuhSTFtKjogSThfCR/Rx5+Rx3lfiPeeiTDs6koIR2DSh/ifCajWx6qYCDckVOBSR7jfiTyiCOYYTXLcCKpXiHEs6fDs6fCsKYjHBwhHx8iHR0iICB1a2ZyaGIiHyCPTx7ogSTlfyTwhyOASB7ziSTngCTrgyT2jCTthCT1iyPuhCX4jSSESh5/Rx6ITR/+kSTYbyDheSLjfSPBaiPacSDdcyCvYSK0ZCGTVSPGcSblhi/ngSPqgSTdu56mXSDaeSP6jiPmfSD5jSQcGhvimFdqUj/TbSJARUsyQE6tbDLOeyzZx7nayr7hsog7Nza/raDjjUF/VTjFtKhKNCB0amRMd0DeAAAASnRSTlMAe/oOkv4TAwgrpnA9n1n9vkYfHPv+xu2r9mgN6uI4Us5ji/n9Ln7G7pO61/K35ZXYiUji5s21zMnb0/r5aMipm2UkYjSGnPy0/l1QcU4AAALOSURBVHhehdJXXxpNFAbwBYIgoICCIKhgi91ETe/9fZ3ZXui92bvp5bPnzCwzK79c+Fxws3/mOXN2BSe+0Tnh1oBKTI7eqvypSUnaHP4XDIVeRn1cucKSJIUj4wMF6cVgYDr2KsrVXE4imUx6mEmHgoH786ZZ3b3H/usZAWW7R3fsAFFNhCrVwmaKdY4ylZdaqEgjI4yxUm7mpAjvjABjbt/QIIZJ1MF+Pu+eY3MMuwEwl2sfEidiBcdaeUl6yrcEkzks38IWMBnG2gUVTrIljSco4LUNGZxRaeZASW4X63Q5hznHyTGqpAfRwU5+WKGtaxC1SQsSKb+tfOyeTHWRDEyuNGhpf7me5EBjoYowFoEZ+gFx7pH+FYaTA0qBfSG6ErI2KcxZJMxVDhSkS1sV4twuNls0wVi4CYpEJ6yHwXEmpHJ5OxJTGNHhMLjH/BP0uQp2GpTwVhHY+orAExLt9ChxWhFWprYcFihaNKLDegZt7U4FuRpbPtbssONgRNECVjm6wYJFrR+5XK/VavVa9byO4L3qpWqMl/qznGlipV46b5xdturYIK//54sND7sAR3SnlfbZxeVFFYkw3MHV9dIQv8CAQ7qlXl+1kUoO78mMsQvwXiRbh8VjEenEqat9NlvUBmLJol2v6jDe89U0Vb7sDQa7k/UjxZSp04mjpw1ewBBRuV6qVbCCRFmzNNE0rOVZP2EZE9F0u10F10qQsr1hVTQ0WTSs7KwP2NA0wjxlUHWCmJRlo5jNELdlYidQaR92RINRp1P4vfPxnSAszis3XLkMD3Gn8+P0dGHh697e3vdvJyf/f4CrLqm8xFx/+IQ+jH/+QgK/wHbek9ZZwhSkq1MbwUW/f+buRJyIfn79+e/TuGBfomfi6UBmjH3NIL19Ffd6J2YEmhX92UqGbpEHzlzzeuNxL6ht9lmG0g5w5PbrtbWJN2/HyGB/AYd1QUewqrrRAAAAAElFTkSuQmCC" alt="MetaMask" class="svelte-18zts4b" />';
            $form_post .='<h3 class="toplogo">Setup MetaMask</h3>';
            $form_post .='</div>';
            $form_post .='<p>To publish a post, Yo will need to install MetaMask to continue. Once you have it installed, go ahead and refresh the page.</p>';
             $form_post .='<p>Make sure to select Ropsten Network to use our dApp for the moment. Thanks!</p>';
            $form_post .='<p><a class="btn btn-primary" href="https://metamask.io/" target="_blank">Install Metamask</a></p>';
           $form_post .='</div>';

			 $form_post .='<div class="immutablepost-form" style="display: none;"><p>Wallet Amount: <strong class="balance">loading...</strong> ETH</p>';
			 $form_post .='<h3>'.$form_title.'</h3>';
			$form_post .='<div id="sectionformstart">';
			 $form_post .='<label for="title">Title:</label>';
			 $form_post .='<input type="text"class="required"  id="title" placeholder="My Title" required />';
             $form_post .='<div class="errorfield"></div>';
			 $form_post .='<label for="description">Description:</label>';
			 $form_post .='<textarea id="description" placeholder="My Description" required></textarea>';
		     $form_post .='<div class="errorfield"></div><br>';
			 $form_post .='<label for="category">Category:</label>';
			 $form_post .='<select id="category" class="required" required>';
			 $form_post .='<option value="Arts & Entertainment">Arts & Entertainment</option>';
			 $form_post .='<option value="Business">Business</option>';
			 $form_post .='<option value="Careers">Careers</option>';
			 $form_post .='<option value="Computers">Computers</option>';
			 $form_post .='<option value="Engineering & Technology">Engineering & Technology</option>';
			 $form_post .='<option value="Environment">Environment</option>';
			 $form_post .='<option value="Fashion">Fashion</option>';
			 $form_post .='<option value="Finance">Finance</option>';
			 $form_post .='<option value="Food & Beverage">Food & Beverage</option>';
			 $form_post .='<option value="Health & Fitness">Health & Fitness</option>';
			 $form_post .='<option value="Hobbies">Hobbies</option>';
			 $form_post .='<option value="Home & Family">Home & Family</option>';
			 $form_post .='<option value="Internet">Internet</option>';
			 $form_post .='<option value="Jobs">Jobs</option>';
			 $form_post .='<option value="Management">Management</option>';
			 $form_post .='<option value="Pets & Animals">Pets & Animals</option>';
			 $form_post .='<option value="Politics">Politics</option>';
			 $form_post .='<option value="Reference & Education">Reference & Education</option>';
			 $form_post .='<option value="Review">Review</option>';
			 $form_post .='<option value="Science">Science</option>';
			 $form_post .='<option value="Self Improvement">Self Improvement</option>';
			 $form_post .='<option value="Society">Society</option>';
			 $form_post .='<option value="Sports & Recreation">Sports & Recreation</option>';
			 $form_post .='<option value="Transportation">Transportation</option>';
			 $form_post .='<option value="Travel & Leisure">Travel & Leisure</option>';
			 $form_post .='<option value="Writing & Speaking">Writing & Speaking</option>';
			 $form_post .='</select>';
			 $form_post .='<br>';

			 $form_post .='<label for="featimg">Upload Feature Image</label><br>';
             $form_post .='<input type="file" id="featimg" />';
             $form_post .='<div id="ipfshash" data-ipfs=""></div><hr/>';
             $form_post .='<div className="nav-form"><button class="rightaligned" id="btnext">Next</button></div>';
             $form_post .='</div>';

			 $form_post .='<div id="sectionformend" style="display:none;">';
             $form_post .='<h3>A bit about yourself</h3>';
             $form_post .='<label for="authorname">Author Name: <span>*</span></label>';
             $form_post .='<input type="text"  class="required" id="authorname" placeholder="Author" required />';
             $form_post .='<div class="errorfield"></div>';
             $form_post .='<label for="authorbio">Your bio: <span>*</span></label><br>';
             $form_post .='<textarea id="authorbio"  class="required" placeholder="Biography" required></textarea>';
             $form_post .='<div class="errorfield"></div><br>';
             $form_post .='<label for="authorlink">Your website(link):</label>';
             $form_post .='<input type="text" id="authorlink" placeholder="Website" /><hr />';
             $form_post .='<label for="compname">Company Name:</label>';
             $form_post .='<input type="text" id="compname" placeholder="Author" />';
             $form_post .='<div class="errorfield"></div>';
             $form_post .='<label for="compcountry">Country: <span>*</span></label><br>';
			 $form_post .='<select id="compcountry"  class="required" name="compcountry" required>';
			 $form_post .='<option value="Afghanistan">Afghanistan</option>
                    <option value="Åland Islands">Åland Islands</option>
                    <option value="Albania">Albania</option>
                    <option value="Algeria">Algeria</option>
                    <option value="American Samoa">American Samoa</option>
                    <option value="Andorra">Andorra</option>
                    <option value="Angola">Angola</option>
                    <option value="Anguilla">Anguilla</option>
                    <option value="Antarctica">Antarctica</option>
                    <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                    <option value="Argentina">Argentina</option>
                    <option value="Armenia">Armenia</option>
                    <option value="Aruba">Aruba</option>
                    <option value="Australia">Australia</option>
                    <option value="Austria">Austria</option>
                    <option value="Azerbaijan">Azerbaijan</option>
                    <option value="Bahamas">Bahamas</option>
                    <option value="Bahrain">Bahrain</option>
                    <option value="Bangladesh">Bangladesh</option>
                    <option value="Barbados">Barbados</option>
                    <option value="Belarus">Belarus</option>
                    <option value="Belgium">Belgium</option>
                    <option value="Belize">Belize</option>
                    <option value="Benin">Benin</option>
                    <option value="Bermuda">Bermuda</option>
                    <option value="Bhutan">Bhutan</option>
                    <option value="Bolivia">Bolivia</option>
                    <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                    <option value="Botswana">Botswana</option>
                    <option value="Bouvet Island">Bouvet Island</option>
                    <option value="Brazil">Brazil</option>
                    <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                    <option value="Brunei Darussalam">Brunei Darussalam</option>
                    <option value="Bulgaria">Bulgaria</option>
                    <option value="Burkina Faso">Burkina Faso</option>
                    <option value="Burundi">Burundi</option>
                    <option value="Cambodia">Cambodia</option>
                    <option value="Cameroon">Cameroon</option>
                    <option value="Canada">Canada</option>
                    <option value="Cape Verde">Cape Verde</option>
                    <option value="Cayman Islands">Cayman Islands</option>
                    <option value="Central African Republic">Central African Republic</option>
                    <option value="Chad">Chad</option>
                    <option value="Chile">Chile</option>
                    <option value="China">China</option>
                    <option value="Christmas Island">Christmas Island</option>
                    <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                    <option value="Colombia">Colombia</option>
                    <option value="Comoros">Comoros</option>
                    <option value="Congo">Congo</option>
                    <option value="Congo The Democratic Republic of The">Congo The Democratic Republic of The</option>
                    <option value="Cook Islands">Cook Islands</option>
                    <option value="Costa Rica">Costa Rica</option>
                    <option value="Cote Divoire">Cote Divoire</option>
                    <option value="Croatia">Croatia</option>
                    <option value="Cuba">Cuba</option>
                    <option value="Cyprus">Cyprus</option>
                    <option value="Czech Republic">Czech Republic</option>
                    <option value="Denmark">Denmark</option>
                    <option value="Djibouti">Djibouti</option>
                    <option value="Dominica">Dominica</option>
                    <option value="Dominican Republic">Dominican Republic</option>
                    <option value="Ecuador">Ecuador</option>
                    <option value="Egypt">Egypt</option>
                    <option value="El Salvador">El Salvador</option>
                    <option value="Equatorial Guinea">Equatorial Guinea</option>
                    <option value="Eritrea">Eritrea</option>
                    <option value="Estonia">Estonia</option>
                    <option value="Ethiopia">Ethiopia</option>
                    <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                    <option value="Faroe Islands">Faroe Islands</option>
                    <option value="Fiji">Fiji</option>
                    <option value="Finland">Finland</option>
                    <option value="France">France</option>
                    <option value="French Guiana">French Guiana</option>
                    <option value="French Polynesia">French Polynesia</option>
                    <option value="French Southern Territories">French Southern Territories</option>
                    <option value="Gabon">Gabon</option>
                    <option value="Gambia">Gambia</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Germany">Germany</option>
                    <option value="Ghana">Ghana</option>
                    <option value="Gibraltar">Gibraltar</option>
                    <option value="Greece">Greece</option>
                    <option value="Greenland">Greenland</option>
                    <option value="Grenada">Grenada</option>
                    <option value="Guadeloupe">Guadeloupe</option>
                    <option value="Guam">Guam</option>
                    <option value="Guatemala">Guatemala</option>
                    <option value="Guernsey">Guernsey</option>
                    <option value="Guinea">Guinea</option>
                    <option value="Guinea-bissau">Guinea-bissau</option>
                    <option value="Guyana">Guyana</option>
                    <option value="Haiti">Haiti</option>
                    <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
                    <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                    <option value="Honduras">Honduras</option>
                    <option value="Hong Kong">Hong Kong</option>
                    <option value="Hungary">Hungary</option>
                    <option value="Iceland">Iceland</option>
                    <option value="India">India</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
                    <option value="Iraq">Iraq</option>
                    <option value="Ireland">Ireland</option>
                    <option value="Isle of Man">Isle of Man</option>
                    <option value="Israel">Israel</option>
                    <option value="Italy">Italy</option>
                    <option value="Jamaica">Jamaica</option>
                    <option value="Japan">Japan</option>
                    <option value="Jersey">Jersey</option>
                    <option value="Jordan">Jordan</option>
                    <option value="Kazakhstan">Kazakhstan</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Kiribati">Kiribati</option>
                    <option value="Korea, Democratic Peoples Republic of">Korea, Democratic Peoples Republic of</option>
                    <option value="Korea Republic of">Korea Republic of</option>
                    <option value="Kuwait">Kuwait</option>
                    <option value="Kyrgyzstan">Kyrgyzstan</option>
                    <option value="Lao Peoples Democratic Republic">Lao Peoples Democratic Republic</option>
                    <option value="Latvia">Latvia</option>
                    <option value="Lebanon">Lebanon</option>
                    <option value="Lesotho">Lesotho</option>
                    <option value="Liberia">Liberia</option>
                    <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                    <option value="Liechtenstein">Liechtenstein</option>
                    <option value="Lithuania">Lithuania</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Macao">Macao</option>
                    <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
                    <option value="Madagascar">Madagascar</option>
                    <option value="Malawi">Malawi</option>
                    <option value="Malaysia">Malaysia</option>
                    <option value="Maldives">Maldives</option>
                    <option value="Mali">Mali</option>
                    <option value="Malta">Malta</option>
                    <option value="Marshall Islands">Marshall Islands</option>
                    <option value="Martinique">Martinique</option>
                    <option value="Mauritania">Mauritania</option>
                    <option value="Mauritius">Mauritius</option>
                    <option value="Mayotte">Mayotte</option>
                    <option value="Mexico">Mexico</option>
                    <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                    <option value="Moldova, Republic of">Moldova, Republic of</option>
                    <option value="Monaco">Monaco</option>
                    <option value="Mongolia">Mongolia</option>
                    <option value="Montenegro">Montenegro</option>
                    <option value="Montserrat">Montserrat</option>
                    <option value="Morocco">Morocco</option>
                    <option value="Mozambique">Mozambique</option>
                    <option value="Myanmar">Myanmar</option>
                    <option value="Namibia">Namibia</option>
                    <option value="Nauru">Nauru</option>
                    <option value="Nepal">Nepal</option>
                    <option value="Netherlands">Netherlands</option>
                    <option value="Netherlands Antilles">Netherlands Antilles</option>
                    <option value="New Caledonia">New Caledonia</option>
                    <option value="New Zealand">New Zealand</option>
                    <option value="Nicaragua">Nicaragua</option>
                    <option value="Niger">Niger</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="Niue">Niue</option>
                    <option value="Norfolk Island">Norfolk Island</option>
                    <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                    <option value="Norway">Norway</option>
                    <option value="Oman">Oman</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Palau">Palau</option>
                    <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
                    <option value="Panama">Panama</option>
                    <option value="Papua New Guinea">Papua New Guinea</option>
                    <option value="Paraguay">Paraguay</option>
                    <option value="Peru">Peru</option>
                    <option value="Philippines">Philippines</option>
                    <option value="Pitcairn">Pitcairn</option>
                    <option value="Poland">Poland</option>
                    <option value="Portugal">Portugal</option>
                    <option value="Puerto Rico">Puerto Rico</option>
                    <option value="Qatar">Qatar</option>
                    <option value="Reunion">Reunion</option>
                    <option value="Romania">Romania</option>
                    <option value="Russian Federation">Russian Federation</option>
                    <option value="Rwanda">Rwanda</option>
                    <option value="Saint Helena">Saint Helena</option>
                    <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                    <option value="Saint Lucia">Saint Lucia</option>
                    <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                    <option value="Saint Vincent and The Grenadines">Saint Vincent and The Grenadines</option>
                    <option value="Samoa">Samoa</option>
                    <option value="San Marino">San Marino</option>
                    <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                    <option value="Saudi Arabia">Saudi Arabia</option>
                    <option value="Senegal">Senegal</option>
                    <option value="Serbia">Serbia</option>
                    <option value="Seychelles">Seychelles</option>
                    <option value="Sierra Leone">Sierra Leone</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Slovakia">Slovakia</option>
                    <option value="Slovenia">Slovenia</option>
                    <option value="Solomon Islands">Solomon Islands</option>
                    <option value="Somalia">Somalia</option>
                    <option value="South Africa">South Africa</option>
                    <option value="South Georgia and The South Sandwich Islands">South Georgia and The South Sandwich Islands</option>
                    <option value="Spain">Spain</option>
                    <option value="Sri Lanka">Sri Lanka</option>
                    <option value="Sudan">Sudan</option>
                    <option value="Suriname">Suriname</option>
                    <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                    <option value="Swaziland">Swaziland</option>
                    <option value="Sweden">Sweden</option>
                    <option value="Switzerland">Switzerland</option>
                    <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                    <option value="Taiwan, Province of China">Taiwan, Province of China</option>
                    <option value="Tajikistan">Tajikistan</option>
                    <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                    <option value="Thailand">Thailand</option>
                    <option value="Timor-leste">Timor-leste</option>
                    <option value="Togo">Togo</option>
                    <option value="Tokelau">Tokelau</option>
                    <option value="Tonga">Tonga</option>
                    <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                    <option value="Tunisia">Tunisia</option>
                    <option value="Turkey">Turkey</option>
                    <option value="Turkmenistan">Turkmenistan</option>
                    <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                    <option value="Tuvalu">Tuvalu</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Ukraine">Ukraine</option>
                    <option value="United Arab Emirates">United Arab Emirates</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="United States">United States</option>
                    <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                    <option value="Uruguay">Uruguay</option>
                    <option value="Uzbekistan">Uzbekistan</option>
                    <option value="Vanuatu">Vanuatu</option>
                    <option value="Venezuela">Venezuela</option>
                    <option value="Viet Nam">Viet Nam</option>
                    <option value="Virgin Islands, British">Virgin Islands, British</option>
                    <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option>
                    <option value="Wallis and Futuna">Wallis and Futuna</option>
                    <option value="Western Sahara">Western Sahara</option>
                    <option value="Yemen">Yemen</option>
                    <option value="Zambia">Zambia</option>
                    <option value="Zimbabwe">Zimbabwe</option></select><br>';
			$form_post .='<label for="compaddress">Company Address: <span>*</span></label>';
            $form_post .='<input type="text"  class="required" id="compaddress" placeholder="Address" required/>';
            $form_post .='<div class="errorfield"></div>';
            $form_post .='<label for="compcontactname">Company Contact Name: <span>*</span></label>';
            $form_post .='<input type="text"  class="required" id="compcontactname" placeholder="Contact Name" required />';
            $form_post .='<div class="errorfield"></div>';
            $form_post .='<label for="compphone">Company Phone: <span>*</span></label>';
            $form_post .='<input type="text"  class="required" id="compphone" placeholder="Phone" required/>';
            $form_post .='<div class="errorfield"></div>';
            $form_post .='<label for="compemail">Company Email: <span>*</span></label>';
            $form_post .='<input type="email"  class="required" id="compemail" placeholder="Email" required/>';
            $form_post .='<div class="errorfield"></div><hr />';
		    $form_post .='<input type="hidden" id="pluginsetup_fullname" value="'.$form_your_fullname.'" />';
            $form_post .='<input type="hidden" id="pluginsetup_company" value="'.$form_company_title.'"/>';
            $form_post .='<input type="hidden" id="pluginsetup_address" value="'.$form_your_address.'" />';
            $form_post .='<input type="hidden" id="pluginsetup_country" value="'.$form_country.'" />';
            $form_post .='<input type="hidden" id="pluginsetup_phone"  value="'.$form_your_phone.'"/>';
            $form_post .='<input type="hidden" id="pluginsetup_email" value="'.$form_your_email.'" />';
			$form_post .='<div className="nav-form">';
            $form_post .=' <button class="leftaligned" id="btprev">Prev</button>';
            $form_post .='<button id="submit-bt" onclick="App.createPostandPay()" data-sharewallet="'.$ether_address.'">Submit Post </button> </div>';
               

			
			 $form_post .='<br><p id="status"></p>';
			 $form_post .='</div>';
			 return $form_post;
		}
		add_shortcode( 'immutable_post', 'immutablepost_shortcode' );

		add_action('wp_ajax_nopriv_send_email_post', 'send_email_post'); // for not logged in users
        add_action('wp_ajax_send_email_post', 'send_email_post');
		function send_email_post()
		{	
                $company_name = $_POST['company_name'];
				$company_contact_name = $_POST['company_contact_name'];
				$company_country = $_POST['company_country'];
				$company_address = $_POST['company_address'];
				$company_phone = $_POST['company_phone'];
				$company_email = $_POST['company_email'];
				$fee = $_POST['fee'];
				$feenogst = $_POST['feenogst'];
				$gstcal = $_POST['gstcal'];
				$invoicenb = $_POST['invoicenb'];
				$posturl = $_POST['posturl'];
				$plugin_company_name = $_POST['plugin_company_name'];
				$plugin_company_contact_name = $_POST['lugin_company_contact_name'];
				$plugin_company_country = $_POST['plugin_company_country'];
				$plugin_company_address = $_POST['plugin_company_address'];
				$plugin_company_phone = $_POST['plugin_company_phone'];
				$plugin_company_email = $_POST['plugin_company_email'];
				$post_date = date("m.d.y");

	
				$headers = 'MIME-Version: 1.0'."\n";
				$headers .= 'Content-type: text/html;'."\n";
				$headers .= 'From:'.$plugin_company_name.' <'.$plugin_company_email.'>'."\n";
				 

				//Message Increaseo
				$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title></title></head><body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"><div id="wrapper" dir="ltr"><table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%"><tr><td align="center" valign="top"><div id="template_header_image"></div><table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="background-color:#ffffff;border:1px solid #d8d8d8;border-radius:3px!important"><tr><td align="center" valign="top"><table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body"><tr><td valign="top" id="body_content"><table border="0" cellpadding="20" cellspacing="0" width="100%"><tr><td valign="top"><div id="body_content_inner">';
				$message .= '<div style="padding: 15px 20px; max-width: 600px; margin: auto;">';
				$message .= '<p>Hi,<br /><br /></p>';
				$message .= '<p>Immutable Post just has a new post from&nbsp;'.$company_contact_name.'</p>';
				$message .= '<p>The post is at: '.$posturl.'</p>';
				$message .= '<p>-----------------------------------------------------------------------------------------------------------<strong><br /><br /><br />INVOICE '.$invoicenb.'</strong><br />Date: '.$post_date.'</p>';
				$message .= '<p>&nbsp;</p>';
				$message .= '<table style="width: 600px; vertical-align: top; float: left;">';
				$message .= '<tbody>';
				$message .= '<tr>';
				$message .= '<td style="width: 300px;" valign="top"><strong>From<br /></strong><br /> Increaseo Pty Ltd<br />3/185 The Entrance Road<br />NSW<br />2250 Erina<br />Australia<br />ABN 69 385 922 934<br />+61 2 8005 1274<br />accounts@increaseo.com<br />https://www.increaseo.com</td>';
				$message .= '<td style="width: 300px;" valign="top"><strong>Bill To</strong> <br /><br />Company Name: '.$company_name.'<br /> Contact Name: '.$company_contact_name.'<br />Country: '.$company_country.' <br />Address: '.$company_address.' <br />Email: '.$company_email.' <br />Phone: '.$company_phone.'<br /><br /><br /></td>';
				$message .= '</tr>';
				$message .= '</tbody>';
				$message .= '</table>';
				$message .= '<p>&nbsp;</p>';
		        if($company_country != "Australia") {
				$message .= '<table style="width: 598px; height: 41px;">';
				$message .= '<tbody>';
				$message .= '<tr>';
				$message .= '<td style="width: 120px;"><strong>Type Description</strong></td>';
				$message .= '<td style="width: 104px;"><strong>Unit price</strong></td>';
				$message .= '<td style="width: 74px;"><strong>Quantity</strong></td>';
				$message .= '<td style="width: 196px;"><strong>Total</strong></td>';
				$message .= '</tr>';
				$message .= '<tr>';
				$message .= '<td style="width: 120px;">Immutable Post</td>';
				$message .= '<td style="width: 104px;">'.$fee.'</td>';
				$message .= '<td style="width: 74px;">1</td>';
				$message .= '<td style="width: 196px;">'.$fee.'</td>';
				$message .= '</tr>';
				$message .= '</tbody>';
				$message .= '</table>';
				} else {
				$message .= '<table style="width: 598px; height: 41px;">';
				$message .= '<tbody>';
				$message .= '<tr>';
				$message .= '<td style="width: 120px;"><strong>Type Description</strong></td>';
				$message .= '<td style="width: 104px;"><strong>Unit price</strong></td>';
				$message .= '<td style="width: 74px;"><strong>Quantity</strong></td>';
				$message .= '<td style="width: 196px;"><strong>Total</strong></td>';
				$message .= '</tr>';
				$message .= '<tr>';
				$message .= '<td style="width: 120px;">Immutable Post</td>';
				$message .= '<td style="width: 104px;">'.$feenogst.' ETH</td>';
				$message .= '<td style="width: 74px;">1</td>';
				$message .= '<td style="width: 196px;">'.$feenogst.' ETH</td>';
				$message .= '</tr>';
				$message .= '</tbody>';
				$message .= '</table>';
				$message .= '<table style="width: 600px; height: 41px;">';
				$message .= '<tbody>';
				$message .= '<tr>';
				$message .= '<td style="width: 141px;">&nbsp;</td>';
				$message .= '<td style="width: 83px;">&nbsp;</td>';
				$message .= '<td style="width: 74px;"><strong>GST (10%)</strong></td>';
				$message .= '<td style="width: 196px;">'.$gstcal.' ETH</td>';
				$message .= '</tr>';
				$message .= '<tr>';
				$message .= '<td style="width: 141px;">&nbsp;</td>';
				$message .= '<td style="width: 83px;">&nbsp;</td>';
				$message .= '<td style="width: 74px;"><strong>Total</strong></td>';
				$message .= '<td style="width: 196px;">'.$fee.'&nbsp;</td>';
				$message .= '</tr>';
				$message .= '</tbody>';
				$message .= '</table>';
				}
				$message .= '<p><br />-----------------------------------------------------------------------------------------------------------</p>';
				$message .= '<p>&nbsp;</p>';
				$message .= '</div>';
				$message .= '</div></td></tr></table></td></tr></table></td></tr><tr><td align="center" valign="top"><table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer"><tr><td valign="top"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td colspan="2" valign="middle" id="credit"></td></tr></table></td></tr></table></td></tr></table></td></tr></table></div></body></html>';


				//Message Poster and Plugin Owner
				$messagetwo = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title></title></head><body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"><div id="wrapper" dir="ltr"><table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%"><tr><td align="center" valign="top"><div id="template_header_image"></div><table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="background-color:#ffffff;border:1px solid #d8d8d8;border-radius:3px!important"><tr><td align="center" valign="top"><table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body"><tr><td valign="top" id="body_content"><table border="0" cellpadding="20" cellspacing="0" width="100%"><tr><td valign="top"><div id="body_content_inner">';
				$messagetwo .= '<div style="padding: 15px 20px; max-width: 600px; margin: auto;">';
				$messagetwo .= '<p>Hi,<br /><br /></p>';
				$messagetwo .= '<p>Immutable Post just has a new post from&nbsp;'.$company_contact_name.'</p>';
				$messagetwo .= '<p>The post is at: '.$posturl.'</p>';
				$messagetwo .= '<p>-----------------------------------------------------------------------------------------------------------<strong><br /><br /><br />INVOICE '.$invoicenb.'</strong><br />Date: '.$post_date.'</p>';
				$messagetwo .= '<p>&nbsp;</p>';
				$messagetwo .= '<table style="width: 600px; vertical-align: top; float: left;">';
				$messagetwo .= '<tbody>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 300px;" valign="top"><strong>From<br /></strong><br /> '.$plugin_company_name.'<br />'.$plugin_company_contact_name.'<br />'.$plugin_company_address.'<br />'.$plugin_company_country.'<br />'.$plugin_company_phone.'<br />'.$plugin_company_phone.'</td>';
				$messagetwo .= '<td style="width: 300px;" valign="top"><strong>Bill To</strong> <br /><br />Company Name: '.$company_name.'<br /> Contact Name: '.$company_contact_name.'<br />Country: '.$company_country.' <br />Address: '.$company_address.' <br />Email: '.$company_email.' <br />Phone: '.$company_phone.'<br /><br /><br /></td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '</tbody>';
				$messagetwo .= '</table>';
				$messagetwo .= '<p>&nbsp;</p>';
		        if($company_country != "Australia") {
				$messagetwo .= '<table style="width: 598px; height: 41px;">';
				$messagetwo .= '<tbody>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 120px;"><strong>Type Description</strong></td>';
				$messagetwo .= '<td style="width: 104px;"><strong>Unit price</strong></td>';
				$messagetwo .= '<td style="width: 74px;"><strong>Quantity</strong></td>';
				$messagetwo .= '<td style="width: 196px;"><strong>Total</strong></td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 120px;">Immutable Post</td>';
				$messagetwo .= '<td style="width: 104px;">'.$fee.'</td>';
				$messagetwo .= '<td style="width: 74px;">1</td>';
				$messagetwo .= '<td style="width: 196px;">'.$fee.'</td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '</tbody>';
				$messagetwo .= '</table>';
				} else {
				$messagetwo .= '<table style="width: 598px; height: 41px;">';
				$messagetwo .= '<tbody>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 120px;"><strong>Type Description</strong></td>';
				$messagetwo .= '<td style="width: 104px;"><strong>Unit price</strong></td>';
				$messagetwo .= '<td style="width: 74px;"><strong>Quantity</strong></td>';
				$messagetwo .= '<td style="width: 196px;"><strong>Total</strong></td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 120px;">Immutable Post</td>';
				$messagetwo .= '<td style="width: 104px;">'.$feenogst.' ETH</td>';
				$messagetwo .= '<td style="width: 74px;">1</td>';
				$messagetwo .= '<td style="width: 196px;">'.$feenogst.' ETH</td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '</tbody>';
				$messagetwo .= '</table>';
				$messagetwo .= '<table style="width: 600px; height: 41px;">';
				$messagetwo .= '<tbody>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 141px;">&nbsp;</td>';
				$messagetwo .= '<td style="width: 83px;">&nbsp;</td>';
				$messagetwo .= '<td style="width: 74px;"><strong>GST (10%)</strong></td>';
				$messagetwo .= '<td style="width: 196px;">'.$gstcal.' ETH</td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '<tr>';
				$messagetwo .= '<td style="width: 141px;">&nbsp;</td>';
				$messagetwo .= '<td style="width: 83px;">&nbsp;</td>';
				$messagetwo .= '<td style="width: 74px;"><strong>Total</strong></td>';
				$messagetwo .= '<td style="width: 196px;">'.$fee.'&nbsp;</td>';
				$messagetwo .= '</tr>';
				$messagetwo .= '</tbody>';
				$messagetwo .= '</table>';
				}
				$messagetwo .= '<p><br />-----------------------------------------------------------------------------------------------------------</p>';
				$messagetwo .= '<p>&nbsp;</p>';
				$messagetwo .= '</div>';
				$messagetwo .= '</div></td></tr></table></td></tr></table></td></tr><tr><td align="center" valign="top"><table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer"><tr><td valign="top"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td colspan="2" valign="middle" id="credit"></td></tr></table></td></tr></table></td></tr></table></td></tr></table></div></body></html>';


				 
				 //Send Email to Increaseo
				 $to = 'troy@increaseo,seb@increaseo.com';
				 $subject  = 'New Immutable Post from Wordpress Plugin Tax Invoice from '.$company_contact_name; 
				 
				 wp_mail( $to, $subject, $message, $headers );
  
				//Send Email to Poster
				$toposter = $company_email;
				$subjectposter  = 'Thanks for posting on Immutable Post'; 
				wp_mail( $toposter, $subjectposter, $messagetwo, $headers );

				//Send email to Plugin installer
				$toplugin = $plugin_company_email;
				$subjectplugin  = 'New Immutable Post Tax Invoice from '.$company_contact_name; 
				wp_mail( $toplugin, $subjectplugin, $messagetwo, $headers );

				echo 'emails sent';
   			    die();
        }

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of ImmutablePost is forbidden' ) ), esc_attr( $this->_version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of ImmutablePost is forbidden' ) ), esc_attr( $this->_version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number() { //phpcs:ignore
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
