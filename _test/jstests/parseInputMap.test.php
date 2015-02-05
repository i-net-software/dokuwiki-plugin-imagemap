<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>parseInputMap - ImageMap Tests</title>
    <link rel="stylesheet" href="//code.jquery.com/qunit/qunit-1.17.1.css">
</head>
<body>
<div id="qunit"></div>
<div id="qunit-fixture"></div>
<script src="//code.jquery.com/qunit/qunit-1.17.1.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="../../jquery.rwdImageMaps.js"></script>
<script src="../../script.js"></script>

<form>
<textarea id="wiki__text">
</textarea>
</form>

<script>
    QUnit.module( "parseInputMap", {
        setup: function( ) {
            DOKU_BASE = 'http://127.0.0.1/~michael/dokuwiki/';
            imagemap = new Imagemap();
            var fade = document.createElement('div');
            fade.id = 'imagemap_fade';
            fade.className = 'imagemap_black_overlay';
            var $myeditor =  jQuery('#'.concat("wiki__text"));
            var myeditor = $myeditor[0];
            myeditor.form.parentNode.appendChild(fade);

            var debugElement = document.createElement('div');
            debugElement.innerHTML='<textarea style="/*display: none;*/" id="imagemap_debug" name="user_eingabe" cols="80" rows="10"></textarea>';
            var popup = document.createElement('div');
            popup.id = 'imagemap_light';
            popup.className = 'imagemap_white_overlay';
            var tableElement= document.createElement('div');
            tableElement.innerHTML=''
            + '<form>'
            + '<input type="hidden" name="id" value="' + 'Some:ID' + '">'
            + '<table id="imageMapTable" border=1 cellspacing=0 cellpadding=5>'
            + '<tr><th>Nr</th><th>X1</th><th>Y1</th><th>Width</th><th>Height</th><th>URL</th><th>Text</th><th>Remove</th></tr>'
            + '</table>'
            + '</form>'
            + unescape("<button onClick=\"imagemap.writeOutput();document.getElementById('imagemap_light').style.display='none';document.getElementById('imagemap_fade').style.display='none';\">Uebernehmen</button><br />")
            + unescape("<button onClick=\"document.getElementById('imagemap_light').style.display='none';document.getElementById('imagemap_fade').style.display='none';\">Abbrechen</button><br />")
            + '<textarea id="output" name="user_eingabe" cols="80" rows="10"></textarea>';

            var cElement = document.createElement('canvas');
            cElement.id = 'canvas';
            cElement.setAttribute("width", "450");
            cElement.setAttribute("height", "250");
            if (typeof(G_vmlCanvasManager) != 'undefined') //IE :-(
                G_vmlCanvasManager.initElement(cElement);
            popup.appendChild(cElement);
            popup.appendChild(debugElement);
            popup.appendChild(tableElement);
            myeditor.form.parentNode.appendChild(popup);
            imagemap.init(myeditor);
        }, teardown: function( ) {
        }
    });
    QUnit.test( "simple map", function( assert ) {
        var line1 = "{{map>512px-catstalkprey.jpg|Bild1422548401308}}\n";
        var line2 = "   * [[foo|bar@ 155,107,268,222]]\n";
        var line3 = "{{<map}}";
        var result = imagemap.parseInput(line1 + line2 + line3);
        assert.equal(result, true, "We expect {{:512px-birdstalkprey.jpg}} to be accepted" );
        assert.equal(imagemap.img.src, 'http://127.0.0.1/~michael/dokuwiki/lib/exe/fetch.php?media=512px-catstalkprey.jpg', 'image source');
        assert.equal(imagemap.filenameWiki,"512px-catstalkprey.jpg",'filenameWiki');
        assert.equal(imagemap.areas[0].x1,155,'x1');
        assert.equal(imagemap.areas[0].y1,107,'y1');
        assert.equal(imagemap.areas[0].width,113,'width');
        assert.equal(imagemap.areas[0].height,115,'height');
        assert.equal(imagemap.areas[0].url,'foo','url');
        assert.equal(imagemap.areas[0].text,'bar','text');
    });

    QUnit.test( "resized map", function( assert ) {
        var line1 = '{{map>512px-catstalkprey.jpg?300&nocache|Bild1422545263962}}\n';
        var line2 = '   * [[foo|bar@ 91,63,157,130]]\n';
        var line3 = '{{<map}}';
        var result = imagemap.parseInput(line1 + line2 + line3);
        assert.equal(result, true, "We expect {{:512px-birdstalkprey.jpg}} to be accepted" );
        assert.equal(imagemap.img.src, 'http://127.0.0.1/~michael/dokuwiki/lib/exe/fetch.php?media=512px-catstalkprey.jpg&nocache', 'image source');
        assert.equal(imagemap.filenameWiki,"512px-catstalkprey.jpg?300&nocache",'filenameWiki');
        assert.equal(imagemap.areas[0].x1,155,'x1');
        assert.equal(imagemap.areas[0].y1,107,'y1');
        assert.equal(imagemap.areas[0].width,113,'width');
        assert.equal(imagemap.areas[0].height,115,'height');
        assert.equal(imagemap.areas[0].url,'foo','url');
        assert.equal(imagemap.areas[0].text,'bar','text');
    });


</script>
</body>
</html>


<form>
    <input type="hidden" name="id" value="' + 'foo' + '">
    <table id="imageMapTable" border=1 cellspacing=0 cellpadding=5>
        <tr><th>Nr</th><th>X1</th><th>Y1</th><th>Width</th><th>Height</th><th>URL</th><th>Text</th><th>Remove</th></tr>
    </table>
</form>
<textarea id="output" name="user_eingabe" cols="80" rows="10"></textarea>
