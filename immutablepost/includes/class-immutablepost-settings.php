<?php
/**
 * Settings class file.
 *
 * @package ImmutablePost/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class ImmutablePost_Settings {

	/**
	 * The single instance of ImmutablePost_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'wpt_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'options', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Immutable Post Settings', 'immutablepost' ),
				'menu_title'  => __( 'Immutable Post Settings', 'immutablepost' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->_token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => '',
				'position'    => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field.
		// If you're not including an image upload then you can leave this function call out.
		wp_enqueue_media();

		wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0', true );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'immutablepost' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
			'title'       => __( 'Setup and Options', 'immutablepost' ),
			'description' => __( '1.Enter a title for the form and your Ethereum wallet address.<br>2.Insert [immutable_post] shortcode to display the form<br>3.Complete your information for Tax invoice ', 'immutablepost' ),
			'fields'      => array(
				array(
					'id'          => 'form_title',
					'label'       => __( 'Form Title', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Form Title', 'immutablepost' ),
				),
				array(
					'id'          => 'text_field',
					'label'       => __( 'Ethereum Wallet Address', 'immutablepost' ),
					'description' => __( 'eg: 0xE77Eac47dcdFeC75Ee8932159d7914a1F055C853.', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '0xE77Eac47dcdFeC75Ee8932159d7914a1F055C853', 'immutablepost' ),
				),
				array(
					'id'          => 'company_title',
					'label'       => __( 'Company Name', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Your company name', 'immutablepost' ),
				),
				array(
					'id'          => 'your_fullname',
					'label'       => __( 'Full name', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Your fullname', 'immutablepost' ),
				),
				array(
					'id'          => 'your_address',
					'label'       => __( 'Full Address', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Full address', 'immutablepost' ),
				),
				
				// array(
				// 	'id'          => 'password_field',
				// 	'label'       => __( 'A Password', 'immutablepost' ),
				// 	'description' => __( 'This is a standard password field.', 'immutablepost' ),
				// 	'type'        => 'password',
				// 	'default'     => '',
				// 	'placeholder' => __( 'Placeholder text', 'immutablepost' ),
				// ),
				// array(
				// 	'id'          => 'secret_text_field',
				// 	'label'       => __( 'Some Secret Text', 'immutablepost' ),
				// 	'description' => __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'wordpress-plugin-template' ),
				// 	'type'        => 'text_secret',
				// 	'default'     => '',
				// 	'placeholder' => __( 'Placeholder text', 'immutablepost' ),
				// ),
				// array(
				// 	'id'          => 'text_block',
				// 	'label'       => __( 'A Text Block', 'immutablepost' ),
				// 	'description' => __( 'This is a standard text area.', 'immutablepost' ),
				// 	'type'        => 'textarea',
				// 	'default'     => '',
				// 	'placeholder' => __( 'Placeholder text for this textarea', 'immutablepost' ),
				// ),
				// array(
				// 	'id'          => 'single_checkbox',
				// 	'label'       => __( 'An Option', 'immutablepost' ),
				// 	'description' => __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'wordpress-plugin-template' ),
				// 	'type'        => 'checkbox',
				// 	'default'     => '',
				// ),
				array(
					'id'          => 'country',
					'label'       => __( 'Country', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'select',
					'options'     => array(
						'Åland Islands'=>'Åland Islands',
                    'Albania'=>'Albania',
                    'Algeria'=>'Algeria',
                    'American Samoa'=>'American Samoa',
                    'Andorra'=>'Andorra',
                    'Angola'=>'Angola',
                    'Anguilla'=>'Anguilla',
                    'Antarctica'=>'Antarctica',
                    'Antigua and Barbuda'=>'Antigua and Barbuda',
                    'Argentina'=>'Argentina',
                    'Armenia'=>'Armenia',
                    'Aruba'=>'Aruba',
                    'Australia'=>'Australia',
                    'Austria'=>'Austria',
                    'Azerbaijan'=>'Azerbaijan',
                    'Bahamas'=>'Bahamas',
                    'Bahrain'=>'Bahrain',
                    'Bangladesh'=>'Bangladesh',
                    'Barbados'=>'Barbados',
                    'Belarus'=>'Belarus',
                    'Belgium'=>'Belgium',
                    'Belize'=>'Belize',
                    'Benin'=>'Benin',
                    'Bermuda'=>'Bermuda',
                    'Bhutan'=>'Bhutan',
                    'Bolivia'=>'Bolivia',
                    'Bosnia and Herzegovina'=>'Bosnia and Herzegovina',
                    'Botswana'=>'Botswana',
                    'Bouvet Island'=>'Bouvet Island',
                    'Brazil'=>'Brazil',
                    'British Indian Ocean Territory'=>'British Indian Ocean Territory',
                    'Brunei Darussalam'=>'Brunei Darussalam',
                    'Bulgaria'=>'Bulgaria',
                    'Burkina Faso'=>'Burkina Faso',
                    'Burundi'=>'Burundi',
                    'Cambodia'=>'Cambodia',
                    'Cameroon'=>'Cameroon',
                    'Canada'=>'Canada',
                    'Cape Verde'=>'Cape Verde',
                    'Cayman Islands'=>'Cayman Islands',
                    'Central African Republic'=>'Central African Republic',
                    'Chad'=>'Chad',
                    'Chile'=>'Chile',
                    'China'=>'China',
                    'Christmas Island'=>'Christmas Island',
                    'Cocos (Keeling) Islands'=>'Cocos (Keeling) Islands',
                    'Colombia'=>'Colombia',
                    'Comoros'=>'Comoros',
                    'Congo'=>'Congo',
                    'Congo, The Democratic Republic of The'=>'Congo, The Democratic Republic of The',
                    'Cook Islands'=>'Cook Islands',
                    'Costa Rica'=>'Costa Rica',
                    'Cote Divoire'=>'Cote Divoire',
                    'Croatia'=>'Croatia',
                    'Cuba'=>'Cuba',
                    'Cyprus'=>'Cyprus',
                    'Czech Republic'=>'Czech Republic',
                    'Denmark'=>'Denmark',
                    'Djibouti'=>'Djibouti',
                    'Dominica'=>'Dominica',
                    'Dominican Republic'=>'Dominican Republic',
                    'Ecuador'=>'Ecuador',
                    'Egypt'=>'Egypt',
                    'El Salvador'=>'El Salvador',
                    'Equatorial Guinea'=>'Equatorial Guinea',
                    'Eritrea'=>'Eritrea',
                    'Estonia'=>'Estonia',
                    'Ethiopia'=>'Ethiopia',
                    'Falkland Islands (Malvinas)'=>'Falkland Islands (Malvinas)',
                    'Faroe Islands'=>'Faroe Islands',
                    'Fiji'=>'Fiji',
                    'Finland'=>'Finland',
                    'France'=>'France',
                    'French Guiana'=>'French Guiana',
                    'French Polynesia'=>'French Polynesia',
                    'French Southern Territories'=>'French Southern Territories',
                    'Gabon'=>'Gabon',
                    'Gambia'=>'Gambia',
                    'Georgia'=>'Georgia',
                    'Germany'=>'Germany',
                    'Ghana'=>'Ghana',
                    'Gibraltar'=>'Gibraltar',
                    'Greece'=>'Greece',
                    'Greenland'=>'Greenland',
                    'Grenada'=>'Grenada',
                    'Guadeloupe'=>'Guadeloupe',
                    'Guam'=>'Guam',
                    'Guatemala'=>'Guatemala',
                    'Guernsey'=>'Guernsey',
                    'Guinea'=>'Guinea',
                    'Guinea-bissau'=>'Guinea-bissau',
                    'Guyana'=>'Guyana',
                    'Haiti'=>'Haiti',
                    'Heard Island and Mcdonald Islands'=>'Heard Island and Mcdonald Islands',
                    'Holy See (Vatican City State)'=>'Holy See (Vatican City State)',
                    'Honduras'=>'Honduras',
                    'Hong Kong'=>'Hong Kong',
                    'Hungary'=>'Hungary',
                    'Iceland'=>'Iceland',
                    'India'=>'India',
                    'Indonesia'=>'Indonesia',
                    'Iran, Islamic Republic of'=>'Iran Islamic Republic of',
                    'Iraq'=>'Iraq',
                    'Ireland'=>'Ireland',
                    'Isle of Man'=>'Isle of Man',
                    'Israel'=>'Israel',
                    'Italy'=>'Italy',
                    'Jamaica'=>'Jamaica',
                    'Japan'=>'Japan',
                    'Jersey'=>'Jersey',
                    'Jordan'=>'Jordan',
                    'Kazakhstan'=>'Kazakhstan',
                    'Kenya'=>'Kenya',
                    'Kiribati'=>'Kiribati',
                    'Korea, Democratic Peoples Republic of' => 'Korea, Democratic Peoples Republic of',
                    'Korea, Republic of'=>'Korea, Republic of',
                    'Kuwait'=>'Kuwait',
                    'Kyrgyzstan'=>'Kyrgyzstan',
                    'Lao Peoples Democratic Republic'=>'Lao Peoples Democratic Republic',
                    'Latvia'=>'Latvia',
                    'Lebanon'=>'Lebanon',
                    'Lesotho'=>'Lesotho',
                    'Liberia'=>'Liberia',
                    'Libyan Arab Jamahiriya'=>'Libyan Arab Jamahiriya',
                    'Liechtenstein'=>'Liechtenstein',
                    'Lithuania'=>'Lithuania',
                    'Luxembourg'=>'Luxembourg',
                    'Macao'=>'Macao',
                    'Macedonia, The Former Yugoslav Republic of'=>'Macedonia, The Former Yugoslav Republic of',
                    'Madagascar'=>'Madagascar',
                    'Malawi'=>'Malawi',
                    'Malaysia'=>'Malaysia',
                    'Maldives'=>'Maldives',
                    'Mali'=>'Mali',
                    'Malta'=>'Malta',
                    'Marshall Islands'=>'Marshall Islands',
                    'Martinique'=>'Martinique',
                    'Mauritania'=>'Mauritania',
                    'Mauritius'=>'Mauritius',
                    'Mayotte'=>'Mayotte',
                    'Mexico'=>'Mexico',
                    'Micronesia, Federated States of'=>'Micronesia, Federated States of',
                    'Moldova, Republic of'=>'Moldova, Republic of',
                    'Monaco'=>'Monaco',
                    'Mongolia'=>'Mongolia',
                    'Montenegro'=>'Montenegro',
                    'Montserrat'=>'Montserrat',
                    'Morocco'=>'Morocco',
                    'Mozambique'=>'Mozambique',
                    'Myanmar'=>'Myanmar',
                    'Namibia'=>'Namibia',
                    'Nauru'=>'Nauru',
                    'Nepal'=>'Nepal',
                    'Netherlands'=>'Netherlands',
                    'Netherlands Antilles'=>'Netherlands Antilles',
                    'New Caledonia'=>'New Caledonia',
                    'New Zealand'=>'New Zealand',
                    'Nicaragua'=>'Nicaragua',
                    'Niger'=>'Niger',
                    'Nigeria'=>'Nigeria',
                    'Niue'=>'Niue',
                    'Norfolk Island'=>'Norfolk Island',
                    'Northern Mariana Islands'=>'Northern Mariana Islands',
                    'Norway'=>'Norway',
                    'Oman'=>'Oman',
                    'Pakistan'=>'Pakistan',
                    'Palau'=>'Palau',
                    'Palestinian Territory, Occupied'=>'Palestinian Territory, Occupied',
                    'Panama'=>'Panama',
                    'Papua New Guinea'=>'Papua New Guinea',
                    'Paraguay'=>'Paraguay',
                    'Peru'=>'Peru',
                    'Philippines'=>'Philippines',
                    'Pitcairn'=>'Pitcairn',
                    'Poland'=>'Poland',
                    'Portugal'=>'Portugal',
                    'Puerto Rico'=>'Puerto Rico',
                    'Qatar'=>'Qatar',
                    'Reunion'=>'Reunion',
                    'Romania'=>'Romania',
                    'Russian Federation'=>'Russian Federation',
                    'Rwanda'=>'Rwanda',
                    'Saint Helena'=>'Saint Helena',
                    'Saint Kitts and Nevis'=>'Saint Kitts and Nevis',
                    'Saint Lucia'=>'Saint Lucia',
                    'Saint Pierre and Miquelon'=>'Saint Pierre and Miquelon',
                    'Saint Vincent and The Grenadines'=>'Saint Vincent and The Grenadines',
                    'Samoa'=>'Samoa',
                    'San Marino'=>'San Marino',
                    'Sao Tome and Principe'=>'Sao Tome and Principe',
                    'Saudi Arabia'=>'Saudi Arabia',
                    'Senegal'=>'Senegal',
                    'Serbia'=>'Serbia',
                    'Seychelles'=>'Seychelles',
                    'Sierra Leone'=>'Sierra Leone',
                    'Singapore'=>'Singapore',
                    'Slovakia'=>'Slovakia',
                    'Slovenia'=>'Slovenia',
                    'Solomon Islands'=>'Solomon Islands',
                    'Somalia'=>'Somalia',
                    'South Africa'=>'South Africa',
                    'South Georgia and The South Sandwich Islands'=>'South Georgia and The South Sandwich Islands',
                    'Spain'=>'Spain',
                    'Sri Lanka'=>'Sri Lanka',
                    'Sudan'=>'Sudan',
                    'Suriname'=>'Suriname',
                    'Svalbard and Jan Mayen'=>'Svalbard and Jan Mayen',
                    'Swaziland'=>'Swaziland',
                    'Sweden'=>'Sweden',
                    'Switzerland'=>'Switzerland',
                    'Syrian Arab Republic'=>'Syrian Arab Republic',
                    'Taiwan, Province of China'=>'Taiwan, Province of China',
                    'Tajikistan'=>'Tajikistan',
                    'Tanzania, United Republic of'=>'Tanzania, United Republic of',
                    'Thailand'=>'Thailand',
                    'Timor-leste'=>'Timor-leste',
                    'Togo'=>'Togo',
                    'Tokelau'=>'Tokelau',
                    'Tonga'=>'Tonga',
                    'Trinidad and Tobago'=>'Trinidad and Tobago',
                    'Tunisia'=>'Tunisia',
                    'Turkey'=>'Turkey',
                    'Turkmenistan'=>'Turkmenistan',
                    'Turks and Caicos Islands'=>'Turks and Caicos Islands',
                    'Tuvalu'=>'Tuvalu',
                    'Uganda'=>'Uganda',
                    'Ukraine'=>'Ukraine',
                    'United Arab Emirates'=>'United Arab Emirates',
                    'United Kingdom'=>'United Kingdom',
                    'United States'=>'United States',
                    'United States Minor Outlying Islands'=>'United States Minor Outlying Islands',
                    'Uruguay'=>'Uruguay',
                    'Uzbekistan'=>'Uzbekistan',
                    'Vanuatu'=>'Vanuatu',
                    'Venezuela'=>'Venezuela',
                    'Viet Nam'=>'Viet Nam',
                    'Virgin Islands, British'=>'Virgin Islands, British',
                    'Virgin Islands, U.S.'=>'Virgin Islands, U.S.',
                    'Wallis and Futuna'=>'Wallis and Futuna',
                    'Western Sahara'=>'Western Sahara',
                    'Yemen'=>'Yemen',
                    'Zambia'=>'Zambia',
                    'Zimbabwe'=>'Zimbabwe',
					),
					'default'     => 'wordpress',
				),
				array(
					'id'          => 'your_phone',
					'label'       => __( 'Phone', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Phone', 'immutablepost' ),
				),
				array(
					'id'          => 'your_email',
					'label'       => __( 'Email', 'immutablepost' ),
					'description' => __( '', 'immutablepost' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Email', 'immutablepost' ),
				),

				// array(
				// 	'id'          => 'radio_buttons',
				// 	'label'       => __( 'Some Options', 'immutablepost' ),
				// 	'description' => __( 'A standard set of radio buttons.', 'immutablepost' ),
				// 	'type'        => 'radio',
				// 	'options'     => array(
				// 		'superman' => 'Superman',
				// 		'batman'   => 'Batman',
				// 		'ironman'  => 'Iron Man',
				// 	),
				// 	'default'     => 'batman',
				// ),
				// array(
				// 	'id'          => 'multiple_checkboxes',
				// 	'label'       => __( 'Some Items', 'immutablepost' ),
				// 	'description' => __( 'You can select multiple items and they will be stored as an array.', 'immutablepost' ),
				// 	'type'        => 'checkbox_multi',
				// 	'options'     => array(
				// 		'square'    => 'Square',
				// 		'circle'    => 'Circle',
				// 		'rectangle' => 'Rectangle',
				// 		'triangle'  => 'Triangle',
				// 	),
				// 	'default'     => array( 'circle', 'triangle' ),
				// ),
			),
			
		);
		
		// $settings['extra'] = array(
		// 	'title'       => __( 'Extra', 'immutablepost' ),
		// 	'description' => __( 'These are some extra input fields that maybe aren\'t as common as the others.', 'immutablepost' ),
		// 	'fields'      => array(
		// 		array(
		// 			'id'          => 'number_field',
		// 			'label'       => __( 'A Number', 'immutablepost' ),
		// 			'description' => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'immutablepost' ),
		// 			'type'        => 'number',
		// 			'default'     => '',
		// 			'placeholder' => __( '42', 'immutablepost' ),
		// 		),
		// 		array(
		// 			'id'          => 'colour_picker',
		// 			'label'       => __( 'Pick a colour', 'immutablepost' ),
		// 			'description' => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'immutablepost' ),
		// 			'type'        => 'color',
		// 			'default'     => '#21759B',
		// 		),
		// 		array(
		// 			'id'          => 'an_image',
		// 			'label'       => __( 'An Image', 'immutablepost' ),
		// 			'description' => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'wordpress-plugin-template' ),
		// 			'type'        => 'image',
		// 			'default'     => '',
		// 			'placeholder' => '',
		// 		),
		// 		array(
		// 			'id'          => 'multi_select_box',
		// 			'label'       => __( 'A Multi-Select Box', 'immutablepost' ),
		// 			'description' => __( 'A standard multi-select box - the saved data is stored as an array.', 'immutablepost' ),
		// 			'type'        => 'select_multi',
		// 			'options'     => array(
		// 				'linux'   => 'Linux',
		// 				'mac'     => 'Mac',
		// 				'windows' => 'Windows',
		// 			),
		// 			'default'     => array( 'linux' ),
		// 		),
		// 	),
		// );

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			//phpcs:disable
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}
			//phpcs:enable

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html; //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Immutable Post Settings', 'immutablepost' ) . '</h2>' . "\n";

			$tab = '';
		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}
		//phpcs:enable

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) { //phpcs:ignore
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) { //phpcs:ignore
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) { //phpcs:ignore
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'wordpress-plugin-template' ) ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			$html         .= '</form>' . "\n";
		$html             .= '</div>' . "\n";

		echo $html; //phpcs:ignore
	}

	/**
	 * Main ImmutablePost_Settings Instance
	 *
	 * Ensures only one instance of ImmutablePost_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see ImmutablePost()
	 * @param object $parent Object instance.
	 * @return object ImmutablePost_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of ImmutablePost_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of ImmutablePost_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()

}
