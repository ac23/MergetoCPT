<?php /**
 * Plugin Name: Merge Child to Parent
 * Plugin URI: https://github.com/iamsayed/read-me-later
 * Description: Add a merge option to pending posts and combine meta on merge while broadcasting back to parent
 * Version: 1.0.0
 * Author: Andrew Clemente
 * Author URI: https://andrewclemente.com
 * License: GPL3
 */

class MergeToParent {

/*
 * Action hooks
 */
public function run() {     
// Enqueue plugin styles and scripts

add_action( 'plugins_loaded', array( $this, 'enqueue_mergeposts_scripts' ) );
add_action( 'plugins_loaded', array( $this, 'merge_styles' ) );
	
// Setup filter hook to show Read Me Later link
add_action( 'post_submitbox_misc_actions', array($this, 'mergeposts_button' ) );
// Setup Ajax action hook
add_action( 'wp_ajax_merge_survey_to_person', array($this, 'merge_survey_to_person' ) );
}   
/**
 * Register plugin styles and scripts
 */
public function register_mergeposts_scripts() {
    wp_register_script( 'mergeposts-script', plugins_url( 'js/read-me-later.js', __FILE__ ), array('jquery'), null, true );
    wp_register_style( 'merge-style', plugin_dir_url( __FILE__ ) .'css/merge.css' );
}   
/**
 * Enqueues plugin-specific scripts.
 */
public function enqueue_mergeposts_scripts() {        
    wp_enqueue_script( 'mergeposts-script' );
	wp_enqueue_style( 'merge-style'); 
    wp_localize_script( 'mergeposts-script', 'readmelater_ajax', array( 'ajax_url' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('rml-nonce') ) );
}   

/**
 * Adds a read me later button at the bottom of each post excerpt that allows logged in users to save those posts in their read me later list.
 *
 * @param string $content
 * @returns string
 */
public function mergeposts_button( $post ) {

if( get_post_type() == 'pending' ) {
	
echo '<div id="merging-actions"><a href="#" class="button button-primary button-large mergeposts_bttn" data-id="' . $post->ID. '" data-source="'. get_post_meta($post->ID, 'sourcepost', true) .'">Verify and Merge to Existing</a></div>';
}
}

	/**
		@brief		Update the parent and the siblings.
		@since		2019-10-29 21:00:42
	**/
	public function update_family( $post_id )
	{
		// Force loading of Back To Parent.
		new \threewp_broadcast\premium_pack\back_to_parent\Back_To_Parent();

		$this->debug( 'Updating parent...' );
		broadcast_back_to_parent()->back_to_parent( $post_id );

		// Flush the bcd cache.
		unset( ThreeWP_Broadcast()->broadcast_data_cache );

		$broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post_id );

		$this->debug( 'Updating children of %s', $broadcast_data );

		switch_to_blog( $broadcast_data->blog_id );

		$bcd = \threewp_broadcast\broadcasting_data::make( $broadcast_data->post_id );
		$api = ThreeWP_Broadcast()->api();
		foreach( $api->_get_post_children( $broadcast_data->post_id ) as $blog_id )
			$bcd->broadcast_to( $blog_id );

		apply_filters( 'threewp_broadcast_broadcast_post', $bcd );

		restore_current_blog();
	}	

/**
 * Hook into wp_ajax_ to save post ids, then display those posts using get_posts() function
 */
public function merge_survey_to_person() {

check_ajax_referer( 'rml-nonce', 'security' );
	
// get values from the data posted by ajax
$post_id = $_POST['post_id'];
$source_id = $_POST['source_id'];
$source = get_post($source_id);

if (!$source) {
echo "could not find the associated person";
die();
}

// get the number of questions from the associated questionnaire 
$myq = get_field('questionid', $post_id);
	
// get final total number of questions
$finalcount = 0;
$countargs = array(
          'post_type' => 'question_set',
          'posts_per_page' => 1,
          'post__in' => array($myq),
        );
        $qcount = new WP_Query( $countargs );

if ( $qcount->have_posts() ) : while ( $qcount->have_posts() ) : $qcount->the_post();
if( have_rows('questions') ): while( have_rows('questions') ): the_row();
if( have_rows('questiongroup') ): while( have_rows('questiongroup') ): the_row(); $finalcount++; endwhile; endif; endwhile; endif; endwhile; endif; wp_reset_query(); wp_reset_postdata(); restore_current_blog();
$newcount = $finalcount - 1;
	
// add same metafield to the source post
update_post_meta( $source_id, "responses", $finalcount );

// update post meta for all available meta
$myvals = get_post_meta($post_id);
foreach($myvals as $key=>$val)
{
	update_post_meta($source_id, $key, $val[0]);
}

// Lets take the broadcast option and automatically apply it here. Note the $source_id is the post id everything is merging into, and it has originally been broadcasted from the parent site so it is already linked. Need to push these changes up to the linked parent
	
// Now send this pending / review post to the trash on run since we don't need it anymore
	
wp_trash_post($post_id);

die();
}
}

$mtp = new MergeToParent();
$mtp->register_mergeposts_scripts();
$mtp->run();