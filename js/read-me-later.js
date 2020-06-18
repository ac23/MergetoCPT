jQuery(document).ready( function(){
jQuery('#merging-actions').on('click', 'a.mergeposts_bttn', function(e) {
e.preventDefault();
var post_id = jQuery(this).data( 'id' );
var source_id = jQuery(this).data( 'source' );
jQuery.ajax({
url : readmelater_ajax.ajax_url,
type : 'post',
data : {
action : 'merge_survey_to_person',
post_id : post_id,
source_id : source_id,
security : readmelater_ajax.check_nonce
},
success : function( response ) {
jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>The post data has successfully been merged and has been moved to the trash.</p></div>')
}
});
jQuery(this).hide();
});
});