/* DOKUWIKI:include_once jquery.imagemapster.js */

jQuery(document).ready(function(e) {
    jQuery('img[usemap]').mapster({
        fillColor: 'ffffff',
        fillOpacity: 0.1,
        wrapClass: true,
        wrapCss: true,
        clickNavigate: true
    });
    
    jQuery(window).resize(function(){
		jQuery('img[usemap]').each(function() {
			$(this).mapster('resize', $(this.offsetParent.offsetParent).width());
		});
    });
});

