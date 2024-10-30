<?php

namespace AsanaPlugins\WhatsApp;

use AsanaPlugins\WhatsApp\Registry\Container;
use AsanaPlugins\WhatsApp\Admin\Admin;
use AsanaPlugins\WhatsApp\API\RestApi;
use AsanaPlugins\WhatsApp\Models\WhatsAppModel;
use AsanaPlugins\WhatsApp\Models\AccountModel;
use AsanaPlugins\WhatsApp\Models\AIContentLayoutModel;
use AsanaPlugins\WhatsApp\Models\ImageLayoutModel;
use AsanaPlugins\WhatsApp\ShortCode\ChatShortCode;
use AsanaPlugins\WhatsApp\WooCommerce\WooCommerceHooks;
use AsanaPlugins\WhatsApp\Blocks\ChatBlock;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public $admin;

	public $settings;

	public $plugin_name;

	public $version;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	protected $container = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function container() {
		if ( ! $this->container instanceof Container ) {
			$this->container = new Container();
		}
		return $this->container;
	}

	public function init() {
		$this->define_constants();

		$this->plugin_name = 'easy-whatsapp';
		$this->version     = ASNP_EWHATSAPP_VERSION;

		register_activation_hook( ASNP_EWHATSAPP_PLUGIN_FILE, array( $this, 'on_activation' ) );
		register_deactivation_hook( ASNP_EWHATSAPP_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
		if ( did_action( 'plugins_loaded' ) ) {
			$this->on_plugins_loaded();
		} else {
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		}
	}

	/**
	 * Install DB and create cron events when activated.
	 *
	 * @return void
	 */
	public function on_activation() {

	}

	/**
	 * Remove WooCommerce Admin scheduled actions on deactivate.
	 *
	 * @return void
	 */
	public function on_deactivation() {

	}

	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		$this->load_plugin_textdomain();

		if ( ! $this->has_satisfied_dependencies() ) {
			add_action( 'admin_init', array( $this, 'deactivate_self' ) );
			add_action( 'admin_notices', array( $this, 'render_dependencies_notice' ) );
			return;
		}

		$this->includes();
	}

	private function define_constants() {
		$this->define( 'ASNP_EWHATSAPP_ABSPATH', dirname( __DIR__ ) . '/' );
		$this->define( 'ASNP_EWHATSAPP_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		$this->define( 'ASNP_EWHATSAPP_PLUGIN_FILE', ASNP_EWHATSAPP_ABSPATH . 'easy-whatsapp.php' );
	}

	/**
	 * Load Localisation files.
	 */
	protected function load_plugin_textdomain() {
		load_plugin_textdomain( 'asnp-easy-whatsapp', false, basename( dirname( __DIR__ ) ) . '/languages' );
	}

	public function includes() {
		$this->register_dependencies();

		$this->settings = new Settings();

		$this->container->get( RestApi::class );

		$this->admin = new Admin( $this->container );
		if ( is_admin() ) {
			$this->admin->init();
		}

		Install::init();

		$this->container->get( Assets::class )->init();

		if ( class_exists( 'WooCommerce' ) ) {
			if ( string_to_bool( get_plugin()->settings->get_setting( 'woocommerceEnabled', true ) ) ) {
				WooCommerceHooks::init();
			}
		}

		add_action( 'init', [ $this, 'add_shortcodes' ] );
		add_action( 'init', [ $this, 'add_blocks' ] );
		add_action( 'init', [ $this, 'compatibility' ] );
		add_filter( 'upload_dir', [ $this, 'upload_dir' ] );
	}

	public function add_shortcodes() {
		add_shortcode( 'asnp_chat', ChatShortCode::class . '::output' );
	}

	public function add_blocks() {
		new ChatBlock( $this->container()->get( Assets::class ) );
	}

	public function compatibility() {
		Compatibility::init();
	}

	protected function register_dependencies() {
		$this->container()->register(
			AccountModel::class,
			function( Container $container ) {
				return new AccountModel();
			}
		);
		$this->container()->register(
			WhatsAppModel::class,
			function( Container $container ) {
				return new WhatsAppModel();
			}
		);
		$this->container()->register(
			AIContentLayoutModel::class,
			function( Container $container ) {
				return new AIContentLayoutModel();
			}
		);
		$this->container()->register(
			ImageLayoutModel::class,
			function( Container $container ) {
				return new ImageLayoutModel();
			}
		);
		$this->container()->register(
			RestApi::class,
			function ( Container $container ) {
				return new RestApi();
			}
		);
		$this->container()->register(
			Assets::class,
			function ( Container $container ) {
				return new Assets();
			}
		);
	}

	/**
	 * Get an array of dependency error messages.
	 *
	 * @return array
	 */
	protected function get_dependency_errors() {
		$errors                    = array();
		$wordpress_version         = get_bloginfo( 'version' );
		$minimum_wordpress_version = '5.0';
		$wordpress_minimum_met     = version_compare( $wordpress_version, $minimum_wordpress_version, '>=' );

		if ( ! $wordpress_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WordPress.org, 2: The minimum WordPress version number */
				__( 'The Live Chat Button plugin requires <a href="%1$s">WordPress</a> %2$s or greater to be installed and active.', 'asnp-easy-whatsapp' ),
				'https://wordpress.org/',
				$minimum_wordpress_version
			);
		}

		return $errors;
	}

	/**
	 * Returns true if all dependencies for the wc-admin plugin are loaded.
	 *
	 * @return bool
	 */
	public function has_satisfied_dependencies() {
		$dependency_errors = $this->get_dependency_errors();
		return 0 === count( $dependency_errors );
	}

	/**
	 * Deactivates this plugin.
	 */
	public function deactivate_self() {
		deactivate_plugins( plugin_basename( ASNP_EWHATSAPP_PLUGIN_FILE ) );
		unset( $_GET['activate'] ); // phpcs:ignore CSRF ok.
	}

	/**
	 * Notify users of the plugin requirements.
	 */
	public function render_dependencies_notice() {
		$message = $this->get_dependency_errors();
		printf( '<div class="error"><p>%s</p></div>', implode( ' ', $message ) ); /* phpcs:ignore xss ok. */
	}

	/**
	 * What type of request is this?
	 *
	 * @since  1.0.0
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	public function upload_dir( $pathdata ) {
		if ( empty( $_FILES['easyWhatsappAccountAvatarFile'] ) ) {
			return $pathdata;
		}

		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['subdir'] = '/easy_whatsapp_uploads/accounts' . ( ! empty( $_POST['id'] && 0 < (int) $_POST['id'] ) ? '/' . (int) $_POST['id'] : '' );
			$pathdata['path']   = $pathdata['path'] . $pathdata['subdir'];
			$pathdata['url']    = $pathdata['url'] . $pathdata['subdir'];
		} else {
			$new_subdir = '/easy_whatsapp_uploads/accounts' . ( ! empty( $_POST['id'] && 0 < (int) $_POST['id'] ) ? '/' . (int) $_POST['id'] : '' );

			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}

		return $pathdata;
	}

	public function is_pro_active() {
		return defined( 'ASNP_EWHATSAPP_PRO_VERSION' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	protected function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Prevent cloning.
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup() {}
}
