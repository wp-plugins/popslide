<?php
/**
 * Popslide back-end
 */

/**
 * Popslide back-end class
 */
class POPSLIDE_BACK {

	public function __construct() {
		global $popslide;

		$this->settings = $popslide->get_settings();

		add_action('admin_menu', array($this, 'add_menu_page'));

		add_action('wp_ajax_popslide_ajax_save_form', array($this, 'ajax_save_form'));

		add_action('wp_ajax_popslide_verify_share', array($this, 'verify_share'));


	}

	/**
	 * Adds menu page
	 * @return void
	 */
	public function add_menu_page() {

		$this->page_hook = add_options_page('Popslide', 'Popslide', 'manage_options', 'popslide', array($this, 'display_admin_page'));

		add_action('admin_print_scripts-'.$this->page_hook, array($this, 'load_admin_assets'));

	}

	/**
	 * Load admin assets
	 * @return void
	 */
	public function load_admin_assets() {

		global $popslide;

		if ( $popslide->is_pro() ) {

			wp_enqueue_script('cs-wp-color-picker', POPSLIDE_JS.'cs-wp-color-picker.min.js', array('jquery', 'wp-color-picker'), null, true);
			wp_enqueue_style('cs-wp-color-picker-style', POPSLIDE_CSS.'cs-wp-color-picker.min.css');

			$deps = array('jquery', 'cs-wp-color-picker');

		} else {

			$deps = array('jquery', 'wp-color-picker');

		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style('popslide-admin-styles', (POPSLIDE_DEBUG) ? POPSLIDE_CSS.'admin.css' : POPSLIDE_CSS.'admin.min.css');
		wp_enqueue_style('popslide-codemirror-styles', (POPSLIDE_DEBUG) ? POPSLIDE_CSS.'codemirror.css' : POPSLIDE_CSS.'codemirror.min.css');

		wp_enqueue_script('popslide-admin-scripts', (POPSLIDE_DEBUG) ? POPSLIDE_JS.'admin.js' : POPSLIDE_JS.'admin.min.js', $deps, null, true);

		wp_enqueue_script('popslide-codemirror-scripts', (POPSLIDE_DEBUG) ? POPSLIDE_JS.'codemirror.js' : POPSLIDE_JS.'codemirror.min.js', array('jquery'), null, true);
		wp_enqueue_script('popslide-codemirror-css-scripts', (POPSLIDE_DEBUG) ? POPSLIDE_JS.'codemirror_css.js' : POPSLIDE_JS.'codemirror_css.min.js', array('jquery', 'popslide-codemirror-scripts'), null, true);

		wp_localize_script( 'popslide-admin-scripts', 'opt', array(
			'pro' => $popslide->is_pro()
		) );

	}

	/**
	 * Displays settings page
	 * @return void
	 */
	public function display_admin_page() {

		global $popslide;

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
	                <?php if (!$popslide->is_pro()): ?>
	                	<a href="#pro" class="nav-tab"><?php _e('PRO', 'popslide'); ?></a>
	                <?php endif ?>
	            </h2>

	            <div class="popslide-tabs-wrapper">
	            	
		            <?php $this->display_settings_tab(); ?>
		            <?php $this->display_content_tab(); ?>
		            <?php $this->display_styling_tab(); ?>
		            <?php if (!$popslide->is_pro()) $this->display_pro_tab(); ?>

	            </div>

            </form>

		</div>

	<?php
	}

	public function display_settings_tab() {

		global $popslide;

		$disabled = ($popslide->is_pro()) ? '' : 'disabled="disabled"';

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
							<p class="description"><?php _e('This will prevent displaying popslide on every visit', 'popslide'); ?></p>
							<br>
							<label for="popslide_cookie_name"><?php _e('Cookie name', 'popslide') ?>: <input id="popslide_cookie_name" type="text" name="cookie[name]" <?php echo $disabled; ?> value="<?php echo $this->settings->cookie->name; ?>" /> <?php if (!$popslide->is_pro()) : ?><a href="#" class="popslide-go-to-pro dashicons dashicons-info" title="<?php _e('Get the PRO version', 'popslide') ?>"></a><?php endif; ?></label>
							<p class="description"><?php _e('Changing cookie name will reset saved cookies on users machine and Popslide will be displayed again', 'popslide'); ?></p>
						</div>
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

		global $popslide;

	?>

		<div id="styling" class="popslide-tab">

			<table class="form-table">

				<tr>
					<th><?php _e('Background color', 'popslide'); ?></th>
					<td>
						<input name="bg_color" class="<?php echo $popslide->is_pro() ? 'cs-wp-color-picker' : 'popslide-colorpicker'; ?>" type="text" required="required" value="<?php echo $this->settings->bg_color; ?>"><br>
						<?php if (!$popslide->is_pro()) : ?>+ transparency setting in <a href="#" class="popslide-go-to-pro" title="<?php _e('Get the PRO version', 'popslide') ?>">PRO</a><?php endif; ?>
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
						<input name="close_button[color]" type="text" min="1" required="required" class="<?php echo $popslide->is_pro() ? 'cs-wp-color-picker' : 'popslide-colorpicker'; ?>" value="<?php echo $this->settings->close_button->color; ?>" /><br>
						<?php if (!$popslide->is_pro()) : ?>+ transparency setting in <a href="#" class="popslide-go-to-pro" title="<?php _e('Get the PRO version', 'popslide') ?>">PRO</a><?php endif; ?>
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

	public function display_pro_tab() {

	?>

		<div id="pro" class="popslide-tab">

			<h3><?php _e('PRO version now available almost for free. Lifetime.', 'popslide'); ?></h3>

			<p><?php _e('All you have to do is spread the good word about Popslide on your Facebook wall.', 'popslide'); ?></p>

			<p><?php _e('Seriously, that\'s all! No money required!', 'popslide'); ?></p>

			<p><?php _e('But don\'t wait to much! Next release will propably come with Pay to Get feature, so I\'m offering it now for (almost) free', 'popslide'); ?></p>

			<p>
				<a href="http://www.wpart.pl/popslide-pro" class="button button-primary" target="_blank"><?php _e('Share', 'popslide'); ?></a> 
				<?php _e('and then', 'popslide'); ?> 
				<input type="text" class="popslide-verify-code" placeholder="<?php _e('paste the activation code', 'popslide'); ?>"><a href="#" class="button button-secondary popslide-verify-share" data-nonce="<?php echo wp_create_nonce('popslide_verify_share'); ?>"><?php _e('Verify', 'popslide'); ?></a>
			</p>

			<p class="popslide-ajax-response-wrapper"></p>

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

		global $popslide;
	
		parse_str($_POST['data'], $data);

		// magic quotes fix
		$data['content'] = stripslashes( $data['content'] );

		// $data = $this->filter_pro_data($data);

    	if (!check_ajax_referer('popslide_save_form', 'nonce')) wp_send_json_error(__('Error while saving settings. Please try again', 'popslide'));

    	update_option('popslide_settings', POPSLIDE::merge_defaults($data, POPSLIDE::defaults()));

    	wp_send_json_success(__('Settings saved.', 'wpmngr'));

	}

	public function verify_share() {

    	if (!check_ajax_referer('popslide_verify_share', 'nonce')) wp_send_json_error();

    	if ( $_POST['code'] != md5(parse_url(site_url(), PHP_URL_HOST)) ) wp_send_json_error(__('Code seems to be not valid. Did you copied it correctly?', 'popslide'));

    	update_option('popslide_lic', $_POST['code']);

    	wp_send_json_success(__('Boom! Done. Thanks and enjoy the PRO version!', 'wpmngr'));

	}

	public function filter_pro_data($data) {

		global $popslide;

    	if ($popslice->is_pro()) return $data;
    	else {

    		unset($data['cookie']['name']);

    	}

    	return POPSLIDE::merge_defaults($data, POPSLIDE::defaults());

	}

}