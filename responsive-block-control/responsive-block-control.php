<?php
declare(strict_types=1);
/**
 *
 * @link              https://saschapaukner.de
 * @since             1.0.0
 * @package           Responsive_Block_Control
 *
 * @wordpress-plugin
 * Plugin Name:       Responsive Block Control
 * Description:       Responsive Block Control adds responsive toggles to a "Visibility" panel of the block editor to hide blocks according to screen width.
 * Version:           1.3.1
 * Author:            Sascha Paukner
 * Author URI:        https://saschapaukner.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       responsive-block-control
 * Domain Path:       /languages
 **/

namespace ResponsiveBlockControl;

use WP_Block_Type_Registry;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Plugin activation.
 */
function activate(): void {
	add_option('responsiveBlockControl', [
		'breakPoints' => [
			'base' => 0,
			'mobile' => 320,
			'tablet' => 740,
			'desktop' => 980,
			'wide' => 1480,
		],
		'addCssToHead' => true,
	]);
}

/**
 * Plugin deactivation.
 */
function deactivate(): void {
	delete_option('responsiveBlockControl');
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate');

add_action('plugins_loaded', __NAMESPACE__ . '\init');

function init(): void {
	(new ResponsiveBlockControl())->register();
}

final class ResponsiveBlockControl
{
	private string $plugin_name = 'responsive-block-control';
	private string $version = '1.3.1';

	/**
	 * Allowed breakpoints.
	 */
	private function allowed_breakpoints(): array {
		return ['mobile', 'tablet', 'desktop', 'wide'];
	}

	public function register(): void {
		add_action('wp_enqueue_scripts', [$this, 'load_frontend_assets']);
		add_action('enqueue_block_assets', [$this, 'load_gutenberg_assets']);
		add_filter('render_block', [$this, 'add_classes'], 10, 2);
		add_action('wp_loaded', [$this, 'register_block_attributes'], 999);
		add_filter('rest_pre_dispatch', [$this, 'sanitize_rest_attributes'], 10, 3);
	}

	public function load_frontend_assets() {
		// js
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'build/js/responsive-block-control-public.js',
			[],
			$this->version,
			false
		);

		$options = get_option('responsiveBlockControl');

		// fallback defaults
		$defaults = [
			'base' => 0,
			'mobile' => 320,
			'tablet' => 740,
			'desktop' => 980,
			'wide' => 1480,
		];

		if (empty($options['breakPoints']) || !is_array($options['breakPoints'])) {
			$options['breakPoints'] = $defaults;
		}

		// Apply filter
		$filtered_breakpoints = (array)apply_filters(
			'responsive_block_control_breakpoints',
			$options['breakPoints']
		);

		// Only allow known breakpoint names
		$sanitized_breakpoints = [];
		foreach ($defaults as $key => $default_value) {
			if (isset($filtered_breakpoints[$key]) && is_numeric($filtered_breakpoints[$key])) {
				$sanitized_breakpoints[$key] = (int)$filtered_breakpoints[$key];
			} else {
				// Use default if missing or invalid
				$sanitized_breakpoints[$key] = $default_value;
			}
		}

		$options['breakPoints'] = $sanitized_breakpoints;

		// Apply addCssToHead filter and sanitize
		$options['addCssToHead'] = (bool)apply_filters(
			'responsive_block_control_addcss',
			$options['addCssToHead'] ?? true
		);

		// ------------------------------
		// Generate custom CSS based on breakpoints
		// ------------------------------
		$rules = apply_filters('responsive_block_control_custom_css_rules', [
			'mobile' => 'clip: rect(1px, 1px, 1px, 1px) !important; clip-path: inset(50%) !important; height: 1px !important; width: 1px !important; margin: -1px !important; overflow: hidden !important; padding: 0 !important; position: absolute !important;',
			'tablet' => 'clip: rect(1px, 1px, 1px, 1px) !important; clip-path: inset(50%) !important; height: 1px !important; width: 1px !important; margin: -1px !important; overflow: hidden !important; padding: 0 !important; position: absolute !important;',
			'desktop' => 'clip: rect(1px, 1px, 1px, 1px) !important; clip-path: inset(50%) !important; height: 1px !important; width: 1px !important; margin: -1px !important; overflow: hidden !important; padding: 0 !important; position: absolute !important;',
			'wide' => 'clip: rect(1px, 1px, 1px, 1px) !important; clip-path: inset(50%) !important; height: 1px !important; width: 1px !important; margin: -1px !important; overflow: hidden !important; padding: 0 !important; position: absolute !important;',
		]);

		$customCss = '';

		$breakKeys = ['mobile', 'tablet', 'desktop', 'wide'];

		foreach ($breakKeys as $index => $key) {
			// The min is now the current breakpoint
			$min = $options['breakPoints'][$key];

			// Max is next breakpoint - 1 (except wide)
			$max = isset($breakKeys[$index + 1]) ? $options['breakPoints'][$breakKeys[$index + 1]] - 1 : null;

			if (!empty($rules[$key])) {
				$customCss .= "@media (min-width: {$min}px)" . ($max !== null ? " and (max-width: {$max}px)" : '') . " {
				  .rbc-is-hidden-on-{$key} {
					{$rules[$key]}
				  }
				}";
			}
		}

		$options['customCss'] = trim($customCss);

		// Localize for JS
		wp_localize_script(
			$this->plugin_name,
			'responsiveBlockControlOptions',
			$options
		);
	}


	public function load_gutenberg_assets(): void {
		if (!is_admin()) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-gutenberg',
			plugin_dir_url(__FILE__) . 'build/js/responsive-block-control-gutenberg.js',
			['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-gutenberg',
			plugin_dir_url(__FILE__) . 'build/css/responsive-block-control-gutenberg.css',
			[],
			$this->version
		);
	}

	/**
	 * Securely add classes to rendered blocks.
	 */
	public function add_classes($block_content, $block) {
		if (!is_string($block_content)) {
			return $block_content;
		}

		$block_content = trim($block_content);

		if (
			!isset($block['attrs']['responsiveBlockControl']) ||
			!is_array($block['attrs']['responsiveBlockControl'])
		) {
			return $block_content;
		}

		$allowed = $this->allowed_breakpoints();
		$clean_classes = [];

		foreach ($block['attrs']['responsiveBlockControl'] as $breakpoint => $value) {
			if (
				in_array($breakpoint, $allowed, true) &&
				is_bool($value) &&
				$value === true
			) {
				$clean_classes[] = 'rbc-is-hidden-on-' . sanitize_html_class($breakpoint);
			}
		}

		if (empty($clean_classes)) {
			return $block_content;
		}

		// Use WP_HTML_Tag_Processor to add classes safely.
		$processor = new \WP_HTML_Tag_Processor($block_content);
		if ($processor->next_tag()) {
			foreach ($clean_classes as $class) {
				$processor->add_class($class);
			}
			return $processor->get_updated_html();
		}

		return $block_content;
	}


	/**
	 * Register attributes for all blocks (SSR compatibility).
	 */
	public function register_block_attributes(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ($registry->get_all_registered() as $block) {
			$block->attributes['responsiveBlockControl'] = [
				'type' => 'object',
				'default' => [
					'mobile' => false,
					'tablet' => false,
					'desktop' => false,
					'wide' => false,
				],
			];
		}
	}

	/**
	 * Sanitize REST attributes to prevent injection.
	 */
	public function sanitize_rest_attributes($result, $server, $request) {
		if (
			strpos($request->get_route(), '/wp/v2/block-renderer') === false ||
			!isset($request['attributes']['responsiveBlockControl'])
		) {
			return $result;
		}

		$clean = [];

		foreach ((array)$request['attributes']['responsiveBlockControl'] as $key => $value) {
			if (in_array($key, $this->allowed_breakpoints(), true)) {
				$clean[$key] = (bool)$value;
			}
		}

		$request['attributes']['responsiveBlockControl'] = $clean;
		return $result;
	}
}
