<?php
/*
Plugin Name: Popslide
Description: Best popup slider plugin
Author: Kuba Mikita
Author URI: http://www.wpart.pl
Version: 1.4.2
License: GPL2
Text Domain: popslide
*/

/*

    Copyright (C) 2014  Kuba Mikita  contact@jmikita.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// General
define('POPSLIDE', plugin_dir_url(__FILE__));
define('POPSLIDE_DIR', plugin_dir_path(__FILE__));

// Assets
define('POPSLIDE_IMAGES', POPSLIDE.'assets/images/');
define('POPSLIDE_IMAGES_DIR', POPSLIDE_DIR.'assets/images/');
define('POPSLIDE_JS', POPSLIDE.'assets/js/');
define('POPSLIDE_JS_DIR', POPSLIDE_DIR.'assets/js/');
define('POPSLIDE_CSS', POPSLIDE.'assets/css/');
define('POPSLIDE_CSS_DIR', POPSLIDE_DIR.'assets/css/');

// Utils
define('POPSLIDE_INC_DIR', POPSLIDE_DIR.'inc/');
define('POPSLIDE_SHORTCODES_DIR', POPSLIDE_DIR.'inc/shortcodes/');
define('POPSLIDE_TEMPLATES_DIR', POPSLIDE_DIR.'templates/');

/**
 * popslide main class
 */
class POPSLIDE {

	/**
	 * @var boolean
	 */
	public $debug = true;

	public $settings;

	public $page_hook;

	public function __construct() {

		require_once(ABSPATH.'wp-includes/pluggable.php');

		global $display_popslide;
		$display_popslide = false;

		add_action('plugins_loaded', array($this, 'load_textdomain'));

		register_activation_hook(__FILE__, array('POPSLIDE', 'activation'));
		register_uninstall_hook(__FILE__, array('POPSLIDE', 'uninstall'));



		$this->settings = get_option('popslide_settings');

		if ($this->settings == false) $this->settings = json_decode(json_encode(self::defaults()));
		else $this->settings = json_decode(json_encode($this->settings));



		add_action('wp_enqueue_scripts', array($this, 'load_front_assets'));
		add_action('wp_head', array($this, 'load_front_css'));

		add_action('admin_menu', array($this, 'add_menu_page'));

		add_action('wp_ajax_popslide_ajax_save_form', array($this, 'ajax_save_form'));

		add_action('wp_ajax_popslide_ajax_save_cookie', array($this, 'ajax_save_cookie'));
		add_action('wp_ajax_nopriv_popslide_ajax_save_cookie', array($this, 'ajax_save_cookie'));

		if ($this->settings->status == 'true') {

			if ( ($this->settings->demo == 'true' && is_super_admin()) || $this->settings->demo == 'false' ) {

				if (wp_is_mobile() && $this->settings->mobile == 'false') {
				} else {

					add_action('init', array($this, 'count_hits'));
					add_action('wp_footer', array($this, 'display_popslide'));

				}

			}

		}

	}

	/**
	 * On plugin activation
	 * @return void
	 */
	public function load_textdomain() {

		load_plugin_textdomain('popslide', false, dirname(plugin_basename(__FILE__ )).'/langs/');

	}

	/**
	 * On plugin activation
	 * @return void
	 */
	static function activation() {

		if(get_option('popslide_settings') !== false)
			return false;

		update_option('popslide_settings', self::defaults());

	}

	/**
	 * On plugin uninstall
	 * @return void
	 */
	static function uninstall() {

		delete_option('popslide_settings');

	}

	/**
	 * Return default settnigs
	 * @return array default settings
	 */
	static function defaults() {

		return array(
			'status' => 'false',
			'demo' => 'false',
			'mobile' => 'false',
			'cookie' => array(
				'active' => 'true',
				'days' => '30'
			),
			'after' => array(
				'hits' => '1',
				'rule' => 'and',
				'seconds' => '10'
			),
			'content' => '',
			'bg_color' => '#f1f1f1',
			'font_color' => '#333333',
			'position' => 'top',
			'close_button' => array(
				'position' => 'top_right',
				'font_size' => '40',
				'color' => '#666666'
			),
			'align' => 'left',
			'width' => array(
				'value' => '100',
				'unit' => '%'
			),
			// 'display' => 'fixed',
			'padding' => array(
				'top' => array(
					'value' => '20',
					'unit' => 'px'
				),
				'right' => array(
					'value' => '20',
					'unit' => 'px'
				),
				'bottom' => array(
					'value' => '20',
					'unit' => 'px'
				),
				'left' => array(
					'value' => '20',
					'unit' => 'px'
				),
			),
			'animation' => array(
				'type' => 'linear',
				'duration' => '300'
			),
			'custom_css' => array(
				'class' => '',
				'status' => 'false',
				'css' => ''
			)
		);

	}

	/**
	 * Load front assets
	 * @return void
	 */
	public function load_front_assets() {
		global $display_popslide;

		if (is_admin() || $display_popslide != true)
			return false; // not this time

		wp_enqueue_style('dashicons');

		wp_enqueue_script('popslide-scripts', ($this->debug) ? POPSLIDE_JS.'front.js' : POPSLIDE_JS.'front.min.js', array('jquery'), null, true);

		wp_localize_script('popslide-scripts', 'popslide_settings', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'timeout' => ($this->settings->after->rule == 'or' && $_SESSION['popslide_hits'] >= $this->settings->after->hits) ? 1000 : 1000 * $this->settings->after->seconds, // 1 second after pageload on display page
			'position' => $this->settings->position,
			// 'animation_type' => $this->settings->animation->type,
			'animation_duration' => $this->settings->animation->duration
		));

	}

	/**
	 * Load front css generated via php
	 * @return void
	 */
	public function load_front_css() {
	?>

		<style type="text/css">

			#popslide {
				position: fixed;
				width: 100%;
				display: none;
				z-index: 9999999;
				background-color: <?php echo $this->settings->bg_color; ?>;
				color: <?php echo $this->settings->font_color; ?>;
				width: <?php echo $this->settings->width->value.$this->settings->width->unit; ?>;
			}

			#popslide.left {
				left: 0;
			}

			#popslide.right {
				right: 0;
			}

			#popslide.center {
				left: 50%;
				margin-left: -<?php echo ($this->settings->width->value/2).$this->settings->width->unit; ?>;
			}

			#popslide .popslide-table {
				display: table;
				width: 100%;
			}

			#popslide .popslide-inner {
				display: table-row;	
			}

			#popslide .popslide-content {
				display: table-cell;
				padding: <?php echo $this->settings->padding->top->value.$this->settings->padding->top->unit.' '.$this->settings->padding->right->value.$this->settings->padding->right->unit.' '.$this->settings->padding->bottom->value.$this->settings->padding->bottom->unit.' '.$this->settings->padding->left->value.$this->settings->padding->left->unit; ?>;
			}

			#popslide .popslide-content p:last-child {
				margin-bottom: 0;
			}

			#popslide .popslide-close {
				display: table-cell;
				padding: <?php echo $this->settings->padding->top->value.$this->settings->padding->top->unit.' '.$this->settings->padding->right->value.$this->settings->padding->right->unit.' '.$this->settings->padding->bottom->value.$this->settings->padding->bottom->unit.' '.$this->settings->padding->left->value.$this->settings->padding->left->unit; ?>;
				color: <?php echo $this->settings->close_button->color; ?>;
				width: <?php echo $this->settings->close_button->font_size; ?>px;
				height: <?php echo $this->settings->close_button->font_size; ?>px;
			}

			#popslide .popslide-close span {
				width: <?php echo $this->settings->close_button->font_size; ?>px;
				height: <?php echo $this->settings->close_button->font_size; ?>px;
			}

			#popslide .popslide-close .dashicons:before {
				cursor: pointer;
				font-size: <?php echo $this->settings->close_button->font_size; ?>px;
			}

			#popslide .popslide-close.bottom_left,
			#popslide .popslide-close.bottom_right {
				vertical-align: bottom;
			}

			#popslide.top {
				top: 0;
			}

			#popslide.bottom {
				bottom: 0;
			}


			/* Wysija integration */
			.popslide-content .wysija-paragraph {
				display: inline-block;
			}

			.popslide-content .widget_wysija_cont p label {
				display: inline-block;
				margin-right: 10px;
			}

			.popslide-content .widget_wysija_cont p .wysija-input {
				margin-right: 10px;
			}

			.popslide-content .widget_wysija_cont .wysija-submit {
				display: inline-block;
				margin-top: 0;
				vertical-align: top;
			}

			<?php if (isset($this->settings->custom_css->status) && $this->settings->custom_css->status == 'true') echo $this->settings->custom_css->css; ?>

		</style>

	<?php
	}

	/**
	 * Load admin assets
	 * @return void
	 */
	public function load_admin_assets() {

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style('popslide-admin-styles', ($this->debug) ? POPSLIDE_CSS.'admin.css' : POPSLIDE_CSS.'admin.min.css');
		wp_enqueue_style('popslide-codemirror-styles', ($this->debug) ? POPSLIDE_CSS.'codemirror.css' : POPSLIDE_CSS.'codemirror.min.css');

		wp_enqueue_script('popslide-admin-scripts', ($this->debug) ? POPSLIDE_JS.'admin.js' : POPSLIDE_JS.'admin.min.js', array('jquery', 'wp-color-picker'), null, true);
		wp_enqueue_script('popslide-codemirror-scripts', ($this->debug) ? POPSLIDE_JS.'codemirror.js' : POPSLIDE_JS.'codemirror.min.js', array('jquery'), null, true);
		wp_enqueue_script('popslide-codemirror-css-scripts', ($this->debug) ? POPSLIDE_JS.'codemirror_css.js' : POPSLIDE_JS.'codemirror_css.min.js', array('jquery', 'popslide-codemirror-scripts'), null, true);

	}

	/**
	 * Adds menu page
	 * @return void
	 */
	public function add_menu_page() {

		$this->page_hook = add_menu_page('Popslide', 'Popslide', 'manage_options', 'popslide', array($this, 'display_admin_page'), 'dashicons-upload', 101.012);

		add_action('admin_print_scripts-'.$this->page_hook, array($this, 'load_admin_assets'));

	}

	/**
	 * Displays settings page
	 * @return void
	 */
	public function display_admin_page() {
	?>

		<div class="wrap">

			<form id="popslide-form">

				<?php wp_nonce_field('popslide_save_form', 'nonce'); ?>

				<h2 class="nav-tab-wrapper popslide-nav">
	                <span class="nav-title">
	                	Popslide &nbsp;
		                <input type="submit" class="button button-primary" value="<?php _e('Save', 'popslide'); ?>" />
		                <img src="<?php echo POPSLIDE_IMAGES.'spinner.gif'; ?>" id="popslide-spinner" />
	                </span>
	                &nbsp;
	                <a href="#settings" class="nav-tab nav-tab-active"><?php _e('Settings', 'popslide'); ?></a>
	                <a href="#content" class="nav-tab"><?php _e('Content', 'popslide'); ?></a>
	                <a href="#styling" class="nav-tab"><?php _e('Styling', 'popslide'); ?></a>
	                <!-- <a href="<?php echo site_url('?popslide-preview='.wp_create_nonce()); ?>" target="_blank" class="nav-tab popslide-preview"><?php _e('Preview', 'popslide'); ?></a> -->
	            </h2>

	            <div class="popslide-tabs-wrapper">
	            	
		            <?php $this->display_settings_tab(); ?>
		            <?php $this->display_content_tab(); ?>
		            <?php $this->display_styling_tab(); ?>

	            </div>

            </form>

		</div>

	<?php
	}

	public function display_settings_tab() {
	?>

		<div id="settings" class="popslide-tab" style="display: block;">

			<table class="form-table">

				<tr>
					<th><?php _e('Status', 'popslide'); ?></th>
					<td>
						<label for="popslide_status">
							<input name="status" type="checkbox" id="popslide_status" value="true" <?php checked('true', $this->settings->status); ?>> 
							<?php _e('Active', 'popslide'); ?>
						</label>
						<p class="description"><?php _e('Popslide will display on the front-end', 'popslide'); ?></p>
					</td>
				</tr>

				<tr>
					<th><?php _e('Preview', 'popslide'); ?></th>
					<td>
						<label for="popslide_demo">
							<input name="demo" type="checkbox" id="popslide_demo" value="true" <?php checked('true', $this->settings->demo); ?>> 
							<?php _e('Active', 'popslide'); ?>
						</label>
						<p class="description"><?php _e('Popslide (if active) will be displayed only for administrators. Cookie (if active) will be not saved', 'popslide'); ?></p>
					</td>
				</tr>

				<tr>
					<th><?php _e('Mobile', 'popslide'); ?></th>
					<td>
						<label for="popslide_mobile">
							<input name="mobile" type="checkbox" id="popslide_mobile" value="true" <?php checked('true', $this->settings->mobile); ?>> 
							<?php _e('Display for mobile', 'popslide'); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th><?php _e('Cookie', 'popslide'); ?></th>
					<td>
						<label for="popslide_cookie">
							<input name="cookie[active]" type="checkbox" id="popslide_cookie" value="true" <?php if (isset($this->settings->cookie->active)) checked('true', $this->settings->cookie->active); ?>> 
							<?php _e('Save cookie on visitor\'s machine', 'popslide'); ?>
						</label>
						<div class="popslide_cookie_more" style="<?php if (!isset($this->settings->cookie->active) || $this->settings->cookie->active != 'true') echo 'display: none;'; ?>">
							<label for="popslide_cookie_days"><?php printf(__('Save for %s days', 'popslide'), '<input id="popslide_cookie_days" type="number" min="1" required="required" class="popslide-number-input" name="cookie[days]" value="'.$this->settings->cookie->days.'" />'); ?></label>
						</div>
						<p class="description"><?php _e('This will prevent displaying popslide on every visit', 'popslide'); ?></p>
					</td>
				</tr>

				<tr>
					<th><?php _e('Display rule', 'popslide'); ?></th>
					<td>

						<label for="popslide_after_hits">
							<?php printf(__('On %s pageview', 'popslide'), '<input name="after[hits]" type="number" min="1" required="required" class="popslide-number-input" id="popslide_after_hits" value="'.$this->settings->after->hits.'" />'); ?>
						</label>


						<select name="after[rule]" id="popslide_after_rule">
							<option <?php selected('and', $this->settings->after->rule); ?> value="and"><?php _e('and', 'popslide'); ?></option>
							<option <?php selected('or', $this->settings->after->rule); ?> value="or"><?php _e('or', 'popslide'); ?></option>
						</select>


						<label for="popslide_after_seconds">
							<?php printf(__('after %s seconds on the page', 'popslide'), '<input name="after[seconds]" type="number" min="0" required="required" class="popslide-number-input" id="popslide_after_seconds" value="'.$this->settings->after->seconds.'" />'); ?>
						</label>

						<!-- <label for="popslide_after_seconds_rule">
							 <?php _e('spent on', 'popslide'); ?> 
							<select name="after[seconds][rule]" id="popslide_after_seconds_rule">
								<option <?php selected('on_page', $this->settings->after->seconds->rule); ?> value="on_page"><?php _e('the current page', 'popslide'); ?></option>
								<option <?php selected('on_website', $this->settings->after->seconds->rule); ?> value="on_website"><?php _e('the website', 'popslide'); ?></option>
							</select>
						</label> -->

					</td>
				</tr>

			</table>

		</div>

	<?php
	}

	public function display_content_tab() {
	?>

		<div id="content" class="popslide-tab">

			<?php wp_editor($this->settings->content, 'popslidecontent', array(
				'textarea_name' => 'content'
			)); ?>

		</div>

	<?php
	}

	public function display_styling_tab() {
	?>

		<div id="styling" class="popslide-tab">

			<table class="form-table">

				<tr>
					<th><?php _e('Background color', 'popslide'); ?></th>
					<td>
						<input name="bg_color" class="popslide-colorpicker" type="text" required="required" value="<?php echo $this->settings->bg_color; ?>"> 
					</td>
				</tr>

				<tr>
					<th><?php _e('Font color', 'popslide'); ?></th>
					<td>
						<input name="font_color" class="popslide-colorpicker" type="text" required="required" value="<?php echo $this->settings->font_color; ?>"> 
					</td>
				</tr>

				<tr>
					<th><?php _e('Position', 'popslide'); ?></th>
					<td class="popslide-image-radio">
						<img alt="top" title="<?php _e('top', 'popslide'); ?>" data-value="top" src="<?php echo POPSLIDE_IMAGES.'position_top.png'; ?>" class="<?php if ($this->settings->position == 'top') echo 'checked'; ?>">
						<img alt="bottom" title="<?php _e('bottom', 'popslide'); ?>" data-value="bottom" src="<?php echo POPSLIDE_IMAGES.'position_bottom.png'; ?>" class="<?php if ($this->settings->position == 'bottom') echo 'checked'; ?>">
						<input name="position" type="hidden" required="required" value="<?php echo $this->settings->position; ?>">
					</td>
				</tr>

				<tr>
					<th><?php _e('Align', 'popslide'); ?></th>
					<td>
						<select name="align">
							<option <?php selected('left', $this->settings->align); ?> value="left"><?php _e('Left', 'popslide'); ?></option>
							<option <?php selected('center', $this->settings->align); ?> value="center"><?php _e('Center', 'popslide'); ?></option>
							<option <?php selected('right', $this->settings->align); ?> value="right"><?php _e('Right', 'popslide'); ?></option>
						</select>
						<p class="description"><?php _e('Whole popslide', 'popslide'); ?></p>
					</td>
				</tr>

				<tr>
					<th><?php _e('Width', 'popslide'); ?></th>
					<td>
						<input name="width[value]" type="number" min="1" required="required" class="popslide-number-input" value="<?php echo $this->settings->width->value; ?>" /><?php $this->unit_select('width[unit]', $this->settings->width->unit); ?>
					</td>
				</tr>

				<!-- <tr>
					<th><?php _e('Display setting', 'popslide'); ?></th>
					<td>
						<select name="display">
							<option <?php selected('cover', $this->settings->display); ?> value="cover"><?php _e('Cover', 'popslide'); ?></option>
							<option <?php selected('push', $this->settings->display); ?> value="push"><?php _e('Push', 'popslide'); ?></option>
						</select>
					</td>
				</tr> -->

				<tr>
					<th><?php _e('Padding', 'popslide'); ?></th>
					<td>
						<span class="popslide-even-width"><?php _e('Top: ', 'popslide'); ?></span> <input name="padding[top][value]" type="number" min="0" required="required" class="popslide-number-input" value="<?php echo $this->settings->padding->top->value; ?>" /><?php $this->unit_select('padding[top][unit]', $this->settings->padding->top->unit); ?><br />
						<span class="popslide-even-width"><?php _e('Right: ', 'popslide'); ?></span> <input name="padding[right][value]" type="number" min="0" required="required" class="popslide-number-input" value="<?php echo $this->settings->padding->right->value; ?>" /><?php $this->unit_select('padding[right][unit]', $this->settings->padding->right->unit); ?><br />
						<span class="popslide-even-width"><?php _e('Bottom: ', 'popslide'); ?></span> <input name="padding[bottom][value]" type="number" min="0" required="required" class="popslide-number-input" value="<?php echo $this->settings->padding->bottom->value; ?>" /><?php $this->unit_select('padding[bottom][unit]', $this->settings->padding->bottom->unit); ?><br />
						<span class="popslide-even-width"><?php _e('Left: ', 'popslide'); ?></span> <input name="padding[left][value]" type="number" min="0" required="required" class="popslide-number-input" value="<?php echo $this->settings->padding->left->value; ?>" /><?php $this->unit_select('padding[left][unit]', $this->settings->padding->left->unit); ?><br />
					</td>
				</tr>

				<tr>
					<th><?php _e('Close button position', 'popslide'); ?></th>
					<td class="popslide-image-radio">
						<img alt="top_left" title="<?php _e('top left', 'popslide'); ?>" data-value="top_left" src="<?php echo POPSLIDE_IMAGES.'close_top_left.png'; ?>" class="<?php if ($this->settings->close_button->position == 'top_left') echo 'checked'; ?>"><br />
						<img alt="top_right" title="<?php _e('top right', 'popslide'); ?>" data-value="top_right" src="<?php echo POPSLIDE_IMAGES.'close_top_right.png'; ?>" class="<?php if ($this->settings->close_button->position == 'top_right') echo 'checked'; ?>"><br />
						<img alt="bottom_right" title="<?php _e('bottom right', 'popslide'); ?>" data-value="bottom_right" src="<?php echo POPSLIDE_IMAGES.'close_bottom_right.png'; ?>" class="<?php if ($this->settings->close_button->position == 'bottom_right') echo 'checked'; ?>"><br />
						<img alt="bottom_left" title="<?php _e('bottom left', 'popslide'); ?>" data-value="bottom_left" src="<?php echo POPSLIDE_IMAGES.'close_bottom_left.png'; ?>" class="<?php if ($this->settings->close_button->position == 'bottom_left') echo 'checked'; ?>"><br />
						<input name="close_button[position]" type="hidden" required="required" value="<?php echo $this->settings->close_button->position; ?>">
					</td>
				</tr>

				<tr>
					<th><?php _e('Close button size', 'popslide'); ?></th>
					<td class="popslide-image-radio">
						<input name="close_button[font_size]" type="number" min="1" required="required" class="popslide-number-input" value="<?php echo $this->settings->close_button->font_size; ?>" /> px
					</td>
				</tr>

				<tr>
					<th><?php _e('Close button color', 'popslide'); ?></th>
					<td class="popslide-image-radio">
						<input name="close_button[color]" type="text" min="1" required="required" class="popslide-colorpicker" value="<?php echo $this->settings->close_button->color; ?>" />
					</td>
				</tr>

				<tr>
					<th><?php _e('Animation', 'popslide'); ?></th>
					<td>
						<!-- <label for="popslide_animation_type">
							<span class="popslide-even-width"><?php _e('Type', 'popslide'); ?>:</span> 
							<select name="animation[type]" id="popslide_animation_type">
								<option <?php selected('linear', $this->settings->animation->type); ?> value="linear">linear</option>
								<option <?php selected('swing', $this->settings->animation->type); ?> value="swing">swing</option>
								<option <?php selected('jswing', $this->settings->animation->type); ?> value="jswing">jswing</option>
								<option <?php selected('easeInQuad', $this->settings->animation->type); ?> value="easeInQuad">easeInQuad</option>
								<option <?php selected('easeInCubic', $this->settings->animation->type); ?> value="easeInCubic">easeInCubic</option>
								<option <?php selected('easeInQuart', $this->settings->animation->type); ?> value="easeInQuart">easeInQuart</option>
								<option <?php selected('easeInQuint', $this->settings->animation->type); ?> value="easeInQuint">easeInQuint</option>
								<option <?php selected('easeInSine', $this->settings->animation->type); ?> value="easeInSine">easeInSine</option>
								<option <?php selected('easeInExpo', $this->settings->animation->type); ?> value="easeInExpo">easeInExpo</option>
								<option <?php selected('easeInCirc', $this->settings->animation->type); ?> value="easeInCirc">easeInCirc</option>
								<option <?php selected('easeInElastic', $this->settings->animation->type); ?> value="easeInElastic">easeInElastic</option>
								<option <?php selected('easeInBack', $this->settings->animation->type); ?> value="easeInBack">easeInBack</option>
								<option <?php selected('easeInBounce', $this->settings->animation->type); ?> value="easeInBounce">easeInBounce</option>
								<option <?php selected('easeOutQuad', $this->settings->animation->type); ?> value="easeOutQuad">easeOutQuad</option>
								<option <?php selected('easeOutCubic', $this->settings->animation->type); ?> value="easeOutCubic">easeOutCubic</option>
								<option <?php selected('easeOutQuart', $this->settings->animation->type); ?> value="easeOutQuart">easeOutQuart</option>
								<option <?php selected('easeOutQuint', $this->settings->animation->type); ?> value="easeOutQuint">easeOutQuint</option>
								<option <?php selected('easeOutSine', $this->settings->animation->type); ?> value="easeOutSine">easeOutSine</option>
								<option <?php selected('easeOutExpo', $this->settings->animation->type); ?> value="easeOutExpo">easeOutExpo</option>
								<option <?php selected('easeOutCirc', $this->settings->animation->type); ?> value="easeOutCirc">easeOutCirc</option>
								<option <?php selected('easeOutElastic', $this->settings->animation->type); ?> value="easeOutElastic">easeOutElastic</option>
								<option <?php selected('easeOutBack', $this->settings->animation->type); ?> value="easeOutBack">easeOutBack</option>
								<option <?php selected('easeOutBounce', $this->settings->animation->type); ?> value="easeOutBounce">easeOutBounce</option>
								<option <?php selected('easeInOutQuad', $this->settings->animation->type); ?> value="easeInOutQuad">easeInOutQuad</option>
								<option <?php selected('easeInOutCubic', $this->settings->animation->type); ?> value="easeInOutCubic">easeInOutCubic</option>
								<option <?php selected('easeInOutQuart', $this->settings->animation->type); ?> value="easeInOutQuart">easeInOutQuart</option>
								<option <?php selected('easeInOutQuint', $this->settings->animation->type); ?> value="easeInOutQuint">easeInOutQuint</option>
								<option <?php selected('easeInOutSine', $this->settings->animation->type); ?> value="easeInOutSine">easeInOutSine</option>
								<option <?php selected('easeInOutExpo', $this->settings->animation->type); ?> value="easeInOutExpo">easeInOutExpo</option>
								<option <?php selected('easeInOutCirc', $this->settings->animation->type); ?> value="easeInOutCirc">easeInOutCirc</option>
								<option <?php selected('easeInOutElastic', $this->settings->animation->type); ?> value="easeInOutElastic">easeInOutElastic</option>
								<option <?php selected('easeInOutBack', $this->settings->animation->type); ?> value="easeInOutBack">easeInOutBack</option>
								<option <?php selected('easeInOutBounce', $this->settings->animation->type); ?> value="easeInOutBounce">easeInOutBounce</option>
							</select>
						</label>	

						<br /> -->

						<label for="popslide_animation_duration">
							<span class="popslide-even-width"><?php _e('Duration', 'popslide'); ?>:</span> 
							<input type="number" min="0" required="required"class="popslide-number-input-wide" name="animation[duration]" id="popslide_animation_duration" value="<?php echo $this->settings->animation->duration; ?>" /> ms
						</label>
					</td>
				</tr>

				<tr>
					<th><?php _e('Custom CSS', 'popslide'); ?></th>
					<td>
						<label for="popslide_custom_css">
							<input name="custom_css[status]" type="checkbox" id="popslide_custom_css" value="true" <?php if (isset($this->settings->custom_css->status)) checked('true', $this->settings->custom_css->status); ?>> 
							<?php _e('Enable custom CSS', 'popslide'); ?>
						</label>
						<div class="popslide_custom_css_more" style="<?php if (!isset($this->settings->custom_css->status) || $this->settings->custom_css->status != 'true') echo 'display: none;'; ?>">
							<span class="popslide-even-width"><?php _e('Custom class', 'popslide'); ?>:</span> <input name="custom_css[class]" type="text" id="popslide_custom_css_class" value="<?php echo $this->settings->custom_css->class; ?>"><br/><br/>
							<textarea id="popslide-custom-css" name="custom_css[css]"><?php if (isset($this->settings->custom_css->css)) echo $this->settings->custom_css->css; ?></textarea>
						</div>
					</td>
				</tr>

			</table>

		</div>

	<?php
	}

	public function unit_select($name, $setting) {
	?>
		<select name="<?php echo $name; ?>">
			<option <?php selected('px', $setting); ?> value="px" selected="selected">px</option>
			<option <?php selected('pt', $setting); ?> value="pt">pt</option>
			<option <?php selected('em', $setting); ?> value="em">em</option>
			<option <?php selected('rem', $setting); ?> value="rem">rem</option>
			<option <?php selected('%', $setting); ?> value="%">%</option>
		</select>
	<?php
	}

	public function ajax_save_form() {
	
		parse_str($_POST['data'], $data);

    	if (!check_ajax_referer('popslide_save_form', 'nonce')) wp_send_json_error(__('Error while saving settings. Please try again', 'popslide'));

    	update_option('popslide_settings', wp_parse_args($data, self::defaults()));

    	wp_send_json_success(__('Settings saved.', 'wpmngr'));

	}

	public function count_hits() {

		if (is_admin() || isset($_COOKIE['popslide_prevent_display']))
			return false;

		global $display_popslide;
	
		session_start();

		if(!isset($_SESSION['popslide_hits'])) {
			$_SESSION['popslide_hits'] = 0;
		}

		$_SESSION['popslide_hits']++;

		if ( ($_SESSION['popslide_hits'] >= $this->settings->after->hits && $this->settings->after->rule == 'and') || $this->settings->after->rule == 'or') {

			$display_popslide = true;

		}

	}

	public function display_popslide() {
	?>

		<div id="popslide" class="<?php echo $this->settings->position; ?> <?php echo $this->settings->align; ?> <?php echo $this->settings->custom_css->class; ?>">
			<div class="popslide-table">
				<div class="popslide-inner">
					<?php if ($this->settings->close_button->position == 'top_left' || $this->settings->close_button->position == 'bottom_left'): ?>
						<div class="popslide-close <?php echo $this->settings->close_button->position; ?>"><span class="dashicons dashicons-no"></span></div>
					<?php endif ?>
						<div class="popslide-content">
							<?php echo apply_filters('the_content', $this->settings->content); ?>
						</div>
					<?php if ($this->settings->close_button->position == 'top_right' || $this->settings->close_button->position == 'bottom_right'): ?>
						<div class="popslide-close <?php echo $this->settings->close_button->position; ?>"><span class="dashicons dashicons-no"></span></div>
					<?php endif ?>
				</div>
			</div>
		</div>

	<?php
	}

	public function ajax_save_cookie() {

		if (isset($this->settings->cookie->active) && $this->settings->cookie->active == 'true' && $this->settings->demo != 'true')
			setcookie('popslide_prevent_display', 'true', time() + (DAY_IN_SECONDS * $this->settings->cookie->days), '/' );

		die(true);

	}

}

$popslide = new POPSLIDE();