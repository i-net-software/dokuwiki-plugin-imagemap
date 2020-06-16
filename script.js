/* DOKUWIKI:include_once jquery.imagemapster.js */

(function($){

    $(function(){
        $('img[usemap]').mapster({
            fillColor: 'ffffff',
            fillOpacity: 0.1,
            wrapClass: true,
            wrapCss: true,
            clickNavigate: true
        });
    });

    $(window).resize(function(){
        $('img[usemap]').each(function() {
            $(this).mapster('resize', $(this.offsetParent).width());
        });
    });

})(jQuery);

function addBtnActionImagemap(btn, props, edid) {
  // Not implemented yet
}