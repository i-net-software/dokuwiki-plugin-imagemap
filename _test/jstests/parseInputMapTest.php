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
    function getTestMsg(expected, actual){
        return " should be between " + (expected-1) + " and " + (expected+1) + ", and is " + actual + ".";
    }
    QUnit.module( "parseInputMap", {
        setup: function( ) {
            DOKU_BASE = /(.*?)lib\/plugins/.exec(window.location.href)[1];
            var xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "GET", DOKU_BASE , false);
            xmlHttp.send();
            JSINFO = [];
            JSINFO['plugin_imagemap_mldummy'] = /plugin_imagemap_mldummy":"(.*?)"/.exec(xmlHttp.responseText)[1];
            this.nsdivider = /wiki\\?(.)dokuwiki-128.png/.exec(JSINFO['plugin_imagemap_mldummy'])[1];

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
        var line1 = "{{map>wiki:dokuwiki-128.png|Bild1422548401308}}\n";
        var line2 = "   * [[foo|bar@ 20,15,100,40]]\n";
        var line3 = "{{<map}}";
        var result = imagemap.parseInput(line1 + line2 + line3);
        assert.equal(result, true, "We expect this map to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE +'lib/exe/fetch.php?media=wiki' + this.nsdivider + 'dokuwiki-128.png', 'image source');
        assert.equal(imagemap.filenameWiki,"wiki:dokuwiki-128.png",'filenameWiki');
        assert.equal(imagemap.areas[0].x1,20,'x1');
        assert.equal(imagemap.areas[0].y1,15,'y1');
        assert.equal(imagemap.areas[0].width,80,'width');
        assert.equal(imagemap.areas[0].height,25,'height');
        assert.equal(imagemap.areas[0].url,'foo','url');
        assert.equal(imagemap.areas[0].text,'bar','text');
    });

    QUnit.test( "resized map", function( assert ) {
        //var line1 = '{{map>512px-catstalkprey.jpg?300&nocache|Bild1422545263962}}\n';
        var line1 = "{{map>wiki:dokuwiki-128.png?300&nocache|Bild1422548401308}}\n";
        var line2 = '   * [[foo|bar@ 47,35,234,94]]\n';
        var line3 = '{{<map}}';
        var result = imagemap.parseInput(line1 + line2 + line3);
        assert.equal(result, true, "We expect this map to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE +'lib/exe/fetch.php?media=wiki' + this.nsdivider + 'dokuwiki-128.png&nocache', 'image source');
        assert.equal(imagemap.filenameWiki,"wiki:dokuwiki-128.png?300&nocache",'filenameWiki');

        var expected = 20;
        var actual = imagemap.areas[0].x1;
        assert.ok(expected-1<=actual&&actual<=expected+1,'x1' + getTestMsg(expected, actual));
        assert.ok(typeof actual === 'number', "x1 should be of type 'number' and is of type '" + typeof actual+"'.");

        expected = 15;
        actual = imagemap.areas[0].y1;
        assert.ok(expected-1<=actual&&actual<=expected+1,'y1' + getTestMsg(expected, actual));
        assert.ok(typeof actual === 'number', "y1 should be of type 'number' and is of type '" + typeof actual+"'.");

        expected = 80;
        actual = imagemap.areas[0].width;
        assert.ok(expected-1<=actual&&actual<=expected+1,'width' + getTestMsg(expected, actual));
        assert.ok(typeof actual === 'number', "width should be of type 'number' and is of type '" + typeof actual+"'.");

        expected = 25;
        actual = imagemap.areas[0].height;
        assert.ok(expected-1<=actual&&actual<=expected+1,'height' + getTestMsg(expected, actual));
        assert.ok(typeof actual === 'number', "height should be of type 'number' and is of type '" + typeof actual+"'.");

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
