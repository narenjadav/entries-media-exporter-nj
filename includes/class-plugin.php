<?php
namespace EMENJ;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main plugin class. Orchestrates bootstrap, initialization, and dependency checks.
 *
 * @package EMENJ
 */
class Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Dependencies instance.
	 *
	 * @var Dependencies
	 */
	private $dependencies;

	/**
	 * Notices instance.
	 *
	 * @var Notices
	 */
	private $notices;

	/**
	 * Admin instance.
	 *
	 * @var Admin|null
	 */
	private $admin = null;

	/**
	 * Export instance.
	 *
	 * @var Export|null
	 */
	private $export = null;

	/**
	 * Get the class instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Private to enforce singleton.
	 */
	private function __construct() {
		$this->dependencies = new Dependencies();
		$this->notices      = new Notices();

		$this->bootstrap();
	}

	private function bootstrap() {
		$checks = $this->dependencies->verify();

		if ( ! $checks['status'] ) {
			// Register notices and stop further initialization.
			$this->notices->set_errors( $checks['errors'] );
			$this->notices->register();
			return;
		}

		$this->init_components();
	}

	/**
	 * Initialize components when dependencies are satisfied.
	 *
	 * @return void
	 */
	private function init_components() {
		$this->admin  = new Admin();
		$this->export = new Export();

		$this->admin->init();
		$this->export->init();
	}

	/**
	 * Get the Admin instance.
	 *
	 * @return Admin|null
	 */
	public function get_admin(): ?Admin {
		return $this->admin;
	}

	/**
	 * Get the Export instance.
	 *
	 * @return Export|null
	 */
	public function get_export(): ?Export {
		return $this->export;
	}
}
