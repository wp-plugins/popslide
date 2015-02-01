<?php
/**
 * Popslide front-end
 */

/**
 * Popslide front-end class
 */
class POPSLIDE_FRONT {

	public function __construct() {
		global $popslide;

		$this->settings = $popslide->get_settings();

		add_action('wp_enqueue_scripts', array($this, 'load_front_assets'));
		add_action('wp_head', array($this, 'load_front_css'));

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
	 * Load front assets
	 * @return void
	 */
	public function load_front_assets() {
		global $display_popslide;

		if (is_admin() || $display_popslide != true)
			return false; // not this time

		wp_enqueue_style('dashicons');

		wp_enqueue_script('popslide-scripts', (POPSLIDE_DEBUG) ? POPSLIDE_JS.'front.js' : POPSLIDE_JS.'front.min.js', array('jquery'), null, true);

		wp_localize_script('popslide-scripts', 'popslide_settings', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'timeout' => ($this->settings->after->rule == 'or' && $_SESSION['popslide_hits'] >= $this->settings->after->hits) ? 1000 : 1000 * $this->settings->after->seconds, // 1 second after pageload on display page
			'position' => $this->settings->position,
			// 'animation_type' => $this->settings->animation->type,
			'animation_duration' => $this->settings->animation->duration
		));

	}

	public function count_hits() {

		if ( is_admin() )
			return false;

		if ( $this->settings->demo == 'false' && isset($_COOKIE[$this->settings->cookie->name]) )
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
							<?php echo do_shortcode( wpautop( $this->settings->content ) ); ?>
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
			setcookie($this->settings->cookie->name, 'true', time() + (DAY_IN_SECONDS * $this->settings->cookie->days), '/' );

		die(true);

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

}