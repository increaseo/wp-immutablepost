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
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
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
			
			//Form to display
			 $form_post ='<div class="immutablepost-form"><p>You have <strong class="balance">loading...</strong> ETH</p>';
			 $form_post .='<h3>'.$form_title.'</h3>';

			 $form_post .='<label for="title">Title:</label>';
			 $form_post .='<input type="text" id="title" placeholder="My Title" /><br>';
			 $form_post .='<label for="description">Description:</label>';
			 $form_post .='<textarea id="description" placeholder="My Description"></textarea><br>';
			 $form_post .='<label for="category">Category:</label>';
			 $form_post .='<select id="category">';
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
			 $form_post .='<br><br>';
			 $form_post .='<button id="submit-bt" onclick="App.createPostandPay()" data-sharewallet="'.$ether_address.'">Submit Post </button>';
			 $form_post .='<br><p id="status"></p>';
			 $form_post .='</div>';
			 return $form_post;
		}
		add_shortcode( 'immutable_post', 'immutablepost_shortcode' );
		

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
