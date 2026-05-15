<?php

if (! defined('ABSPATH')) {
	exit;
}

class PC_Builder_Plugin {
	private $admin;
	private $frontend;

	public function __construct() {
		$this->admin    = new PC_Builder_Admin();
		$this->frontend = new PC_Builder_Frontend();
	}

	public function run() {
		$this->admin->hooks();
		$this->frontend->hooks();
	}
}
