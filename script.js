/* DOKUWIKI:include_once jquery.imagemapster.js */
/* DOKUWIKI:include ImageMappingEditor.js */

(function ($) {

    $(function () {
        $('img[usemap]').mapster({
            fillColor: 'ffffff',
            fillOpacity: 0.1,
            wrapClass: true,
            wrapCss: true,
            stroke: true,
            strokeColor: "3320FF",
            strokeOpacity: 0.8,
            strokeWidth: 4,
            clickNavigate: true
        });
    });

    $(window).resize(function () {
        $('img[usemap]').each(function () {
            $(this).mapster('resize', $(this.offsetParent).width());
        });
    });

})(jQuery);



function addBtnActionImagemap(btn, props, edid) {
    jQuery(btn).on('click', function () {
        // get current selection
        const area = document.getElementById(edid);
        const sel = DWgetSelection(area);
        new ImageMappingEditor(sel);
    });
}
