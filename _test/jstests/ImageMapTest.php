<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ImageMap Tests</title>
    <link rel="stylesheet" href="//code.jquery.com/qunit/qunit-1.17.1.css">
</head>
<body>
<div id="qunit"></div>
<div id="qunit-fixture"></div>
<script src="//code.jquery.com/qunit/qunit-1.17.1.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="../../jquery.rwdImageMaps.js"></script>
<script src="../../script.js"></script>



<script>
    QUnit.module( "parseInput", {
        setup: function( ) {
            imagemap = new Imagemap();
            imagemap.img = new Image();
            DOKU_BASE = /(.*?)lib\/plugins/.exec(window.location.href)[1];
            var xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "GET", DOKU_BASE , false);
            xmlHttp.send();
            JSINFO = [];
            JSINFO['plugin_imagemap_mldummy'] = /plugin_imagemap_mldummy":"(.*?)"/.exec(xmlHttp.responseText)[1];
            this.nsdivider = /wiki\\?(.)dokuwiki-128.png/.exec(JSINFO['plugin_imagemap_mldummy'])[1];
        }, teardown: function( ) {
        }
    });
    QUnit.test( "{{:512px-catstalkprey.jpg|}} passing", function( assert ) {
        var result = imagemap.parseInput('{{:512px-catstalkprey.jpg|}}');
        assert.equal(result, true, "We expect {{:512px-catstalkprey.jpg|}} to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE + 'lib/exe/fetch.php?media=' + this.nsdivider + '512px-catstalkprey.jpg', 'image source');
        assert.equal(imagemap.filenameWiki,":512px-catstalkprey.jpg",'filenameWiki');
    });

    QUnit.test( "{{:512px-dogstalkprey.jpg|some title}} passing", function( assert ) {
        var result = imagemap.parseInput('{{:512px-dogstalkprey.jpg|some title}}');
        assert.equal(result, true, "We expect {{:512px-dogstalkprey.jpg|some title}} to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE + 'lib/exe/fetch.php?media=' + this.nsdivider + '512px-dogstalkprey.jpg', 'image source');
        assert.equal(imagemap.filenameWiki,":512px-dogstalkprey.jpg",'filenameWiki');
    });

    QUnit.test( "{{:512px-birdstalkprey.jpg}} passing", function( assert ) {
        var result = imagemap.parseInput('{{:512px-birdstalkprey.jpg}}');
        assert.equal(result, true, "We expect {{:512px-birdstalkprey.jpg}} to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE + 'lib/exe/fetch.php?media=' + this.nsdivider + '512px-birdstalkprey.jpg', 'image source');
        assert.equal(imagemap.filenameWiki,":512px-birdstalkprey.jpg",'filenameWiki');
    });

    QUnit.test( "{{:512px-fishstalkprey.jpg?200x300&recache}} passing", function( assert ) {
        var result = imagemap.parseInput('{{foo:512px-fishstalkprey.jpg?200x300&recache}}');
        assert.equal(result, true, "We expect {{:512px-birdstalkprey.jpg}} to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE + 'lib/exe/fetch.php?media=foo' + this.nsdivider + '512px-fishstalkprey.jpg&recache', 'image source');
        assert.equal(imagemap.filenameWiki,"foo:512px-fishstalkprey.jpg?200x300&recache",'filenameWiki');
        assert.equal(imagemap.setWidth,'200','imagemap.setWidth');
        assert.equal(imagemap.setHight,'300','imagemap.setHight');
    });

    QUnit.test( "{{:512px-fishstalkprey.jpg?200&nocache}} passing", function( assert ) {
        var result = imagemap.parseInput('{{foo:512px-fishstalkprey.jpg?200&nocache}}');
        assert.equal(result, true, "We expect {{:512px-birdstalkprey.jpg}} to be accepted" );
        assert.equal(imagemap.img.src, DOKU_BASE + 'lib/exe/fetch.php?media=foo' + this.nsdivider + '512px-fishstalkprey.jpg&nocache', 'image source');
        assert.equal(imagemap.filenameWiki,"foo:512px-fishstalkprey.jpg?200&nocache",'filenameWiki');
        assert.equal(imagemap.setWidth,'200','imagemap.setWidth');
        assert.equal(imagemap.setHight,undefined,'imagemap.setHight');
    });

    QUnit.test( "link to other server passing", function( assert ) {
        var result = imagemap.parseInput('{{http://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Tortoiseshell_she-cat.JPG/320px-Tortoiseshell_she-cat.JPG?320|By Toyah (Own work) [Public domain], via Wikimedia Commons}}');
        assert.equal(result, true, "We expect the result to be true" );
        assert.equal(imagemap.img.src, 'http://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Tortoiseshell_she-cat.JPG/320px-Tortoiseshell_she-cat.JPG?320', 'We should get the link to the server');
    });


    QUnit.module( "getOptions", {
        setup: function( ) {
            imagemap = new Imagemap();
            imagemap.img = new Image();
            DOKU_BASE = 'http://127.0.0.1/~michael/dokuwiki/';
        }, teardown: function( ) {
        }
    });
    QUnit.test( "200&nocache", function( assert ) {
        var result = imagemap.getOptions('200&nocache');
        assert.equal(result, 'nocache', "nocache" );
        assert.equal(imagemap.setWidth, '200', 'imagemap.setWidth');
    });

    QUnit.test( "recache&200x120", function( assert ) {
        var result = imagemap.getOptions('recache&200x120');
        assert.equal(result, 'recache', "recache" );
        assert.equal(imagemap.setWidth, '200', 'imagemap.setWidth');
        assert.equal(imagemap.setHight, '120', 'imagemap.setHight');
    });

    QUnit.test( "recache&200x120&direct", function( assert ) {
        var result = imagemap.getOptions('recache&200x120&direct');
        assert.equal(result, 'recache&direct', "recache&direct" );
        assert.equal(imagemap.setWidth, '200', 'imagemap.setWidth');
        assert.equal(imagemap.setHight, '120', 'imagemap.setHight');
    });

    QUnit.test( "recache&direct", function( assert ) {
        var result = imagemap.getOptions('recache&direct');
        assert.equal(result, 'recache&direct', "recache&direct" );
        assert.equal(imagemap.setWidth, undefined, 'imagemap.setWidth');
        assert.equal(imagemap.setHight, undefined, 'imagemap.setHight');
    });



    QUnit.module( "capture groups", {
        setup: function( ) {
            //reg = new RegExp('\{\{(.*?)(\?.*?)(?:[\|]|[\}]{2})(?:(.*?)\}\})?');
            reg = /\{\{(.*?)(?:\?(.*?))?(?:[\|]|[\}]{2})(?:(.*?)\}\})?/;
        }, teardown: function( ) {
        }
    });
    QUnit.test( "{{:512px-catstalkprey.jpg?200}}", function( assert ) {
        var text = '{{:512px-catstalkprey.jpg?200}}';
        assert.equal( reg.test(text), true, "We expect the result to be true" );
        var match = reg.exec(text);
        var wikilink = match[1];
        var size = match[2];
        var title = match[3];
        assert.equal(wikilink, ':512px-catstalkprey.jpg', 'wikilink');
        assert.equal(title, undefined, 'title');
        assert.equal(size, '200', 'size');
        assert.deepEqual(match, [
            "{{:512px-catstalkprey.jpg?200}}",
            ":512px-catstalkprey.jpg",
            '200',
            undefined
        ], 'complete match');
    });

    QUnit.test( "{{:512px-catstalkprey.jpg}}", function( assert ) {
        var text = '{{:512px-catstalkprey.jpg}}';
        assert.equal( reg.test(text), true, "We expect the result to be true" );
        var match = reg.exec(text);
        var wikilink = match[1];
        var title = match[3];
        assert.equal(wikilink, ':512px-catstalkprey.jpg', 'wikilink');
        assert.equal(title, undefined, 'title');
        assert.deepEqual(match, [
        "{{:512px-catstalkprey.jpg}}",
            ":512px-catstalkprey.jpg",
            undefined,
            undefined
        ], 'complete match');
    });

    QUnit.test( "{{:512px-catstalkprey.jpg|foo}}", function( assert ) {
        var text = '{{:512px-catstalkprey.jpg|foo}}';
        assert.equal( reg.test(text), true, "We expect the result to be true" );
        var match = reg.exec(text);
        var wikilink = match[1];
        var title = match[3];
        assert.equal(wikilink, ':512px-catstalkprey.jpg', 'wikilink');
        assert.equal(title, 'foo', 'title');
        var expected_complete_match = ["{{:512px-catstalkprey.jpg|foo}}",":512px-catstalkprey.jpg",undefined,"foo"];
        assert.deepEqual(match, expected_complete_match, 'complete match');
    });

    QUnit.test( "true false", function( assert ) {
        assert.equal(!undefined, true, 'undefined');
    });

</script>
</body>
</html>








<!--<?php
$myarray=array('a'=>'b',2,3=>'k');
print_r($myarray);
print('b');
?>-->
