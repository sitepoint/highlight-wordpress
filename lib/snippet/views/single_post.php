<?php
// Commenting rules
// Body comments:
//  Show if passed the start date
// End Comments:
//  Show if no comments have been added
//  Close if comments are closed

class Snippet_Views_SinglePost{
	public function init(){
		if( !$this->active_for_post() ){
			return;
		}
		if( is_singular() && ($this->post()->post_type === 'post' || $this->post()->post_type === 'sp_solution') ){
			wp_enqueue_script( 'snippet-client' );
			wp_enqueue_style( 'snippet-client' );
			add_filter( 'wp_head', Array($this, 'inject_account_key') );
			add_filter( 'wp_footer', Array($this, 'snippet_setup_script'), 30 );
		}
	}

	// Please note, using date functions for maximum
	// backwards compatibility
	public function active_for_post() {
		$start_time = strtotime(get_option('snippet_start_date'));
		// Start date option is not valid
		if( !$start_time ){
			return true;
		}

		// Can't get publish date, turn them on
		$publish_time = strtotime(get_the_date());
		if(!$publish_time){
			return true;
		}

		// Start time was after this posts published date
		if( $start_time > $publish_time){
			return false;
		}

		return true;
	}

	public function writable_for_post(){
		// Wordpress comments are off, so disabled comments
		if(!$this->wordpress_comments_active()){
			return false;
		}
		// Can't get publish date, turn them on
		$publish_time = strtotime(get_the_date());
		if(!$publish_time){
			return true;
		}

		// Have we set an active period?
		$active_period = intval(get_option('snippet_active_period'));
		if( $active_period === 0){
			return true;
		}

		// Is the time the article has been published greater than the
		// active period?
		if( (time() - $publish_time) > ($active_period * 60 *60 *24) ){
			return false;
		}

		return true;
	}

	// Returns true if wordpress comments are active
	public function wordpress_comments_active(){
		return $this->post()->comment_status != "closed";
	}

	public function has_wordpress_comments() {
		return intval($this->post()->comment_count) > 0;
	}

	public function post(){
		if(isset($this->_post)){
			return $this->_post;
		}
		$this->_post = get_post();
		return $this->_post;
	}

	public function inject_account_key(){
		if( get_option('account_key') ){ ?>
			<meta title="snippet-account-key" value="<?php echo get_option('snippet_account_key') ?>" />
		<?php }
	}

	public function snippet_setup_script(){ ?>
		<script type="text/javascript">
		var snippet = new Highlight("<?php echo get_option('snippet_account_key') ?>", "<?php echo $this->post_id() ?>", {
	 contentSelector: ".<?php echo get_option('snippet_post_content_class', SNIPPET_CONTENT_CLASS_DEFAULT) ?>",
 	titleSelector: ".<?php echo get_option('snippet_post_title_class', SNIPPET_TITLE_CLASS_DEFAULT) ?>",
 	readOnly: <?php echo $this->writable_for_post() ? 'false' : 'true'?>,
	twitterUsername: <?php $u = get_option('snippet_twitter_username'); echo $u ? "'" . $u . "'": 'false' ?>,
 	endOfArticleComments: <?php echo $this->has_wordpress_comments() ? 'false' : 'true' ?>});
		snippet.start();
		</script>
	<?php }

	protected function post_id(){
		return str_replace(
			"{id}", get_the_ID(), get_option('post_id_format', SNIPPET_POST_ID_DEFAULT)
		);
	}
}
