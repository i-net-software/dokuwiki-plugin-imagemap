/* DOKUWIKI:include_once jquery.rwdImageMaps.js */

jQuery(document).ready(function(e) {
    jQuery('img.imap').rwdImageMaps();
});

function addBtnActionImagemap(btn, props, edid) {
    imagemap = new Imagemap();
    var fade = document.createElement('div');
    fade.id = 'imagemap_fade';
    fade.className = 'imagemap_black_overlay';
    var $myeditor =  jQuery('#'.concat(edid));
    var myeditor = $myeditor[0];
    myeditor.form.parentNode.appendChild(fade);

    var debugElement = document.createElement('div');
    debugElement.innerHTML='<textarea style="display: none;" id="imagemap_debug" name="user_eingabe" cols="80" rows="10"></textarea>';
    var popup = document.createElement('div');
    popup.id = 'imagemap_light';
    popup.className = 'imagemap_white_overlay';
    var tableElement= document.createElement('div');
    tableElement.innerHTML=''
    + '<form>'
    + '<input type="hidden" name="id" value="' + JSINFO.id + '">'
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

    jQuery(btn).click(function(){
    //addEvent(btn,'click',function(){
        imagemap.selection = DWgetSelection(imagemap.dokuwikiText);
        //reset all stuff
        imagemap.areas = new Array();
        for (i = imagemap.table.rows.length-1; i > 0; i--)
            imagemap.table.deleteRow(i);

        //parse input
        if (!imagemap.parseInput(imagemap.selection.getText())) {
            alert(LANG['plugins']['imagemap']['please_mark']);
        } else {
            document.getElementById('imagemap_fade').style.display = 'block';
            document.getElementById('imagemap_light').style.display = 'block';
        }
        return false;
    });
    return true;
}

function Imagemap () {
    this.makeStruct = function(names) {
        var names = names.split(',');
        var count = names.length;
        function constructor() {
            for (var i = 0; i < count; i++) {
                this[names[i]] = arguments[i];
            }
        }
        return constructor;
    };

    this.Item = this.makeStruct("x1,y1,width,height,url,text");
    this.areas;
    this.img;
    this.dokuwikiText;
    this.MODE_NEW = 0;
    this.MODE_MOVE = 1;
    this.MODE_RESIZE = 2;
    this.RESIZE_UPPER_LEFT = 0;
    this.RESIZE_LOWER_LEFT = 1;
    this.RESIZE_UPPER_RIGHT = 2;
    this.RESIZE_LOWER_RIGHT = 3;
    this.ctx;
    this.table;
    this.canvas;
    this.selection;
    this.filenameWiki;
    this.drag = {
        'active' : false,
        'mode' : -1,
        'pos': -1
    };
    this.linkwizbackup;
    this.ie_version = -1;
    this.intervalID = -1;
    this.debug;

    this.init = function(dokuwikiText) {
        imagemap.areas = new Array();
        imagemap.dokuwikiText = dokuwikiText;
        imagemap.table = document.getElementById('imageMapTable');
        imagemap.canvas = document.getElementById('canvas');
        imagemap.ctx = imagemap.canvas.getContext('2d');
        imagemap.img = new Image();
        //this.img.src = '/_media/erp-plm-mes.jpg';
        imagemap.img.onload = function() {
            imagemap.canvas.width = imagemap.img.width;
            imagemap.canvas.height = imagemap.img.height;
            //setInterval(imagemap.draw, 25);
            imagemap.draw();
            imagemap.canvas.onselectstart = function () { return false; }; // IE: mouse cursor
            imagemap.canvas.onmousedown = imagemap.myDown;
            imagemap.canvas.onmouseup = imagemap.myUp;
        };
        imagemap.output = document.getElementById('output');
        var rv = -1; // Return value assumes failure.
        if (navigator.appName == 'Microsoft Internet Explorer') {
            var ua = navigator.userAgent;
            var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
            if (re.exec(ua) !== null)
                rv = parseFloat(RegExp.$1);
        }
        imagemap.ie_version = rv;

    };

    this.draw = function() {
        var ctx = imagemap.ctx;
        var canvas = imagemap.canvas;
        var img = imagemap.img;
        var areas = imagemap.areas;

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img,0,0, img.width, img.height);
        ctx.strokeStyle = "red";
        ctx.font = '20px sans-serif';
        for (var i = 0; i<areas.length; i++)
            if (areas[i].x1 != -1) {
                ctx.strokeRect(areas[i].x1, areas[i].y1, areas[i].width, areas[i].height);
                ctx.fillStyle = 'red';
                ctx.fillRect(areas[i].x1+((areas[i].width)/2)-10, areas[i].y1, 20, 20);
                ctx.fillRect(areas[i].x1, areas[i].y1, 10, 10);
                ctx.fillRect(areas[i].x1+areas[i].width-10, areas[i].y1, 10, 10);
                ctx.fillRect(areas[i].x1, areas[i].y1+areas[i].height-10, 10, 10);
                ctx.fillRect(areas[i].x1+areas[i].width-10, areas[i].y1+areas[i].height-10, 10, 10);
                ctx.fillStyle = 'white';
                if (ctx.fillText)
                    ctx.fillText(i+1, areas[i].x1+((areas[i].width)/2)-6, areas[i].y1+17);
            }
    };

    /**
     * Check if somebody click on the label to move
     */
    this.getRectMove = function(x, y) {
        for (var i = 0; i<this.areas.length; i++)
            if (this.areas[i].x1 != -1) {
                if (((this.areas[i].x1+((this.areas[i].width)/2)-10) <= x) &&
                    (this.areas[i].y1 <= y) &&
                    ((this.areas[i].x1+((this.areas[i].width)/2)+10) >= x) &&
                    (this.areas[i].y1+20 >= y))
                    return i;
            }
        return -1;
    };

    this.getRectResize = function(x, y) {
        for (var i = 0; i<imagemap.areas.length; i++) {
            if (imagemap.areas[i].x1 != -1) {
                //upper left corner
                if ((imagemap.areas[i].x1 <= x) && (x <= imagemap.areas[i].x1+10) &&
                    (imagemap.areas[i].y1 <= y) && (y <= imagemap.areas[i].y1+10)) {
                    imagemap.drag.pos = imagemap.RESIZE_UPPER_LEFT;
                    return i;
                }
                if ((imagemap.areas[i].x1 <= x) && (x <= imagemap.areas[i].x1+10) &&
                    (imagemap.areas[i].y1+imagemap.areas[i].height-10 <= y) && (y <= imagemap.areas[i].y1+imagemap.areas[i].height)) {
                    imagemap.drag.pos = imagemap.RESIZE_LOWER_LEFT;
                    return i;
                }
                if ((imagemap.areas[i].x1+imagemap.areas[i].width-10 <= x) && (x <= imagemap.areas[i].x1+imagemap.areas[i].width) &&
                    (imagemap.areas[i].y1 <= y) && (y <= imagemap.areas[i].y1+10)) {
                    imagemap.drag.pos = imagemap.RESIZE_UPPER_RIGHT;
                    return i;
                }
                if ((imagemap.areas[i].x1+imagemap.areas[i].width-10 <= x) && (x <= imagemap.areas[i].x1+imagemap.areas[i].width) &&
                    (imagemap.areas[i].y1+imagemap.areas[i].height-10 <= y) && (y <= imagemap.areas[i].y1+imagemap.areas[i].height)) {
                    imagemap.drag.pos = imagemap.RESIZE_LOWER_RIGHT;
                    return i;
                }
            }
        }

        return -1;
    };

    this.myMove = function(e){
        if (imagemap.drag.active){
            x = imagemap.mouseX(e);
            y = imagemap.mouseY(e);
            if ((imagemap.areas.length > 0) && (imagemap.drag.mode == imagemap.MODE_NEW)) {
                imagemap.areas[imagemap.areas.length-1].width = x-imagemap.areas[imagemap.areas.length-1].x1;
                imagemap.areas[imagemap.areas.length-1].height = y-imagemap.areas[imagemap.areas.length-1].y1;
                //return;
            }
            if (imagemap.drag.mode == imagemap.MODE_MOVE) {
                imagemap.areas[imagemap.drag.index].x1 = (x-((imagemap.areas[imagemap.drag.index].width)/2));
                imagemap.areas[imagemap.drag.index].y1 = y-10;
            }
            if (imagemap.drag.mode == imagemap.MODE_RESIZE) {
                if (imagemap.drag.pos == imagemap.RESIZE_UPPER_LEFT) {
                    imagemap.areas[imagemap.drag.index].width -= x - imagemap.areas[imagemap.drag.index].x1;
                    imagemap.areas[imagemap.drag.index].height -= y - imagemap.areas[imagemap.drag.index].y1;
                    imagemap.areas[imagemap.drag.index].x1 = x;
                    imagemap.areas[imagemap.drag.index].y1 = y;
                }
                if (imagemap.drag.pos == imagemap.RESIZE_LOWER_LEFT) {
                    imagemap.areas[imagemap.drag.index].width -= x - imagemap.areas[imagemap.drag.index].x1;
                    imagemap.areas[imagemap.drag.index].height = y-imagemap.areas[imagemap.drag.index].y1;
                    imagemap.areas[imagemap.drag.index].x1 = x;
                }
                if (imagemap.drag.pos == imagemap.RESIZE_LOWER_RIGHT) {
                    imagemap.areas[imagemap.drag.index].width = x-imagemap.areas[imagemap.drag.index].x1;
                    imagemap.areas[imagemap.drag.index].height = y-imagemap.areas[imagemap.drag.index].y1;
                    //return;
                }
                if (imagemap.drag.pos == imagemap.RESIZE_UPPER_RIGHT) {
                    imagemap.areas[imagemap.drag.index].width = x - imagemap.areas[imagemap.drag.index].x1;
                    imagemap.areas[imagemap.drag.index].height -= y-imagemap.areas[imagemap.drag.index].y1;
                    imagemap.areas[imagemap.drag.index].y1 = y;
                    //return;
                }
            }
            imagemap.draw();
            imagemap.debug("myMove X="+ x + " Y=" + y);
        }
    };

    this.mouseX = function(evt) {
        if (!evt) {
            evt = window.event;
        }
        var returnX;
        if (evt.offsetX) {
            var offsetX_result = evt.offsetX;
            returnX = offsetX_result;
        } else if (evt.layerX) {
            var layerX_result = evt.layerX + (document.documentElement.scrollLeft ?
                    document.documentElement.scrollLeft :
                    document.body.scrollLeft);
            returnX = layerX_result;
        } else if (evt.clientX) {
            var consoleX_result = evt.clientX - 10 - document.getElementById('imagemap_light').offsetLeft + document.getElementById('imagemap_light').scrollLeft;
            returnX = consoleX_result;
        } else {
            returnX = null;
        }

        return returnX;
    };

    this.mouseY = function(evt) {
        if (!evt) {
            evt = window.event;
        }
        var returnY;
        if (evt.offsetY) {
            var offsetY_result = evt.offsetY;
            returnY = offsetY_result;
        } else if (evt.layerY) {
            var layerY_result = evt.layerY + (document.documentElement.scrollLeft ?
                    document.documentElement.scrollLeft :
                    document.body.scrollLeft);
            returnY = layerY_result;
        } else if (evt.clientY) {
            var consoleY_result = evt.clientY - 10 - document.getElementById('imagemap_light').offsetLeft + document.getElementById('imagemap_light').scrollLeft;
            returnY = consoleY_result;
        } else {
            returnY = null;
        }


        return returnY;
    };

    this.myDown = function(e){
        var drag = imagemap.drag;

        /*if( !e ) {
         //if the browser did not pass the event information to the
         //function, we will have to obtain it from the event register
         if( window.event ) {
         //Internet Explorer 8-
         e = window.event;
         } else {
         //total failure, we have no way of referencing the event
         return;
         }
         } */
        if (drag.active) {
            imagemap.myUp();
            return false;
        }
        drag.active = true;
        x = imagemap.mouseX(e);
        y = imagemap.mouseY(e);
        drag.index = imagemap.getRectMove(x, y);
        if (drag.index >= 0) {
            drag.mode = imagemap.MODE_MOVE;
        } else {
            drag.index = imagemap.getRectResize(x, y);
            if (drag.index >= 0) {
                drag.mode = imagemap.MODE_RESIZE;
            } else {
                drag.mode = imagemap.MODE_NEW;
                imagemap.areas[imagemap.areas.length] = new imagemap.Item(x, y, 0, 0, "", "");
            }
        }
        imagemap.debug('myDown X=' + x + ' Y=' + y);
        imagemap.canvas.onmousemove = imagemap.myMove;
        return false;
    };

    this.myUp = function(){
        imagemap.drag.active = false;
        imagemap.canvas.onmousemove = null;
        if (imagemap.areas.length <= 0) return;

        if (imagemap.drag.mode == imagemap.MODE_NEW) {
            var row = imagemap.table.insertRow(imagemap.table.rows.length);
            var iter = imagemap.areas.length-1;
            var area = imagemap.areas[iter];
            //area to small -->accident click
            if ((area.width < 10) && (area.height < 10)) {
                imagemap.areas.splice(iter, 1);
                return;
            }

            var cellID = row.insertCell(0);
            cellID.appendChild(document.createTextNode(iter + 1));

            var cellX1 = row.insertCell(1);
            var inputX1 = document.createElement('input');
            inputX1.type = 'text'; inputX1.size = 4; inputX1.onkeyup = imagemap.textChanged;
            inputX1.onblur = imagemap.textChanged;
            inputX1.value = area.x1;
            inputX1.name = 'imt_x1_' + iter;
            cellX1.appendChild(inputX1);

            var cellY1 = row.insertCell(2);
            var inputY1 = document.createElement('input');
            inputY1.type = 'text'; inputY1.size = 4; inputY1.onkeyup = imagemap.textChanged;
            inputY1.onblur = imagemap.textChanged;
            inputY1.value = area.y1;
            inputY1.name = 'imt_y1_' + iter;
            cellY1.appendChild(inputY1);

            var cellX2 = row.insertCell(3);
            var inputX2 = document.createElement('input');
            inputX2.type = 'text'; inputX2.size = 4; inputX2.onkeyup = imagemap.textChanged;
            inputX2.onblur = imagemap.textChanged;
            inputX2.value = area.width;
            inputX2.name = 'imt_x2_' + iter;
            cellX2.appendChild(inputX2);

            var cellY2 = row.insertCell(4);
            var inputY2 = document.createElement('input');
            inputY2.type = 'text'; inputY2.size = 4; inputY2.onkeyup = imagemap.textChanged;
            inputY2.onblur = imagemap.textChanged;
            inputY2.value = area.height;
            inputY2.name = 'imt_y2_' + iter;
            cellY2.appendChild(inputY2);

            var cellURL = row.insertCell(5);
            var inputURL = document.createElement('input');
            inputURL.type = 'text';
            inputURL.size = 40;
            inputURL.onkeyup = imagemap.textChanged;
            inputURL.oninput = imagemap.textChanged;
            inputURL.onblur = imagemap.textChanged;
            inputURL.value = area.url;
            inputURL.name = 'imt_url_' + iter;
            cellURL.appendChild(inputURL);

            var buttonURL = document.createElement('button');
            buttonURL.innerHTML = '<img src="'+DOKU_BASE+'./lib/images/toolbar/link.png">';
            jQuery(buttonURL).click(function(){
                dw_linkwiz.textArea = inputURL;
                dw_linkwiz.toggle();
                return false;
            });
            cellURL.appendChild(buttonURL);

            var cellText = row.insertCell(6);
            var inputText = document.createElement('input');
            inputText.type = 'text'; inputText.size = 15; inputText.onkeyup = imagemap.textChanged;
            inputText.onblur = imagemap.textChanged;
            inputText.value = area.text;
            inputText.name = 'imt_text_' + iter;
            cellText.appendChild(inputText);

            var cellBtn = row.insertCell(7);
            var inputBtn = document.createElement('input');
            inputBtn.type = 'button'; inputBtn.onclick = imagemap.removeClicked;
            inputBtn.name = 'imt_btn_' + iter;
            inputBtn.value = 'X';
            cellBtn.appendChild(inputBtn);
        }
        imagemap.updateOutput();
    };

    this.removeClicked = function(e) {
        //IE :-(
        if (!e) e = window.event;
        if (!e.target) e.target = e.srcElement;

        var idx = e.target.name.substring(8);

        //mark as deleted
        imagemap.areas[idx].x1 = -1;

        var target = e.target;
        do {
            if ( target.nodeName.toUpperCase() == 'TR' ) {
                target.parentNode.removeChild(target);
                break;
            }
        } while ( target = target.parentNode );

        imagemap.updateOutput();
    };

    this.textChanged = function(e) {
        //IE :-(
        if (!e) e = window.event;
        if (!e.target) e.target = e.srcElement;

        var name = new Array();
        name = e.target.name.split('_');
        var area = imagemap.areas[name[2]];
        if (name[1] == 'x1') area.x1 = parseInt(e.target.value);
        if (name[1] == 'y1') area.y1 = parseInt(e.target.value);
        if (name[1] == 'x2') area.width = parseInt(e.target.value);
        if (name[1] == 'y2') area.height = parseInt(e.target.value);
        if (name[1] == 'url') area.url = e.target.value;
        if (name[1] == 'text') area.text = e.target.value;
        imagemap.updateOutput();
    };


    this.updateOutput = function() {
        var text = "{{map>" + this.filenameWiki + "|" + this.imageID + "}}\n";
        var scaleX = 1;
        var scaleY = 1;
        if (imagemap.setWidth !== undefined && imagemap.setWidth != '0' ){
            scaleX = Number(imagemap.setWidth)/imagemap.img.width;
            scaleY = scaleX;
        } else if (imagemap.setHight !== undefined && imagemap.setHight != '0' ){
            scaleY = Number(imagemap.setHight)/imagemap.img.height;
            scaleX = scaleY;
        }
        for (var i = 0; i<this.areas.length; i++) {
            if (this.areas[i].x1 == -1) continue; //check if deleted
            var x1 = Math.round(this.areas[i].x1 * scaleX);
            var y1 = Math.round(this.areas[i].y1 * scaleY);
            var x2 = Math.round((this.areas[i].x1 + this.areas[i].width) * scaleX);
            var y2 = Math.round((this.areas[i].y1 + this.areas[i].height) * scaleY);

            var search = /\[\[(.*)\|\]\]/;
            if (search.exec(this.areas[i].url)) {
                this.areas[i].url = search[1];
            }

            var url = this.areas[i].url + "|" + ((this.areas[i].text && (this.areas[i].text.length > 0)) ? this.areas[i].text : this.areas[i].url);
            text += "   * [[" + url + "@ " + x1 + "," + y1 + "," + x2 + "," + y2 + "]]\n";
        }
        text += "{{<map}}";
        imagemap.output.value = text;
        imagemap.draw();
    };

    this.writeOutput = function() {
        this.updateOutput();
        pasteText(imagemap.selection, document.getElementById('output').value);
    };

    this.debug = function (text) {
        document.getElementById('imagemap_debug').value = document.getElementById('imagemap_debug').value + "\n" + text;
    };

    this.getOptions = function (imageoptions) {
        var options = imageoptions.split('&');
        var imagesize;
        for (var sizeposition = 0; sizeposition < options.length; sizeposition++) {
            if (/\d+x\d+/.test(options[sizeposition])) {
                imagesize = /(\d+)x(\d+)/.exec(options[sizeposition]);
                imagemap.setWidth = imagesize[1];
                imagemap.setHight = imagesize[2];
                break;
            } else if (/\d+/.test(options[sizeposition])) {
                imagemap.setWidth = options[sizeposition];
                break;
            }
        }
        if (sizeposition !=  options.length){
            options.splice(sizeposition, 1);
        }
        return options.join('&');
    };

    this.parseInput = function(text) {
        /*
         match[1] is the image name, match[2] are the imageoptions or undefined and match[3] is the title or undefined
        */
        var reg = /\{\{(.*?)(?:\?(.*?))?(?:[\|]|[\}]{2})(?:(.*?)\}\})?/;
        var textA = text.split("\n");
        if (textA.length > 1) return this.parseInputImageMap(textA);
        else if (!reg.test(text)) return false;
        //if (!reg.test(text)) return false;
        var match = reg.exec(text);
        var imageName = match[1];
        var imageoptions = match[2];

        imagemap.filenameWiki = imageName;

        var timestamp = Number(new Date());

        imagemap.imageID = 'Bild' + timestamp;

        if (!/http\:\/\/|ftp\:\/\//.test(imageName)) {
            //we have a local image

            if (/wiki\\?(.)dokuwiki-128.png/.exec(JSINFO.plugin_imagemap_mldummy)[1]=='/') {
                //namespaces are not divided by colon, but rewritten to be divided by slash:
                imageName = imageName.replace(/:/g, "/");
            }
            imageName = DOKU_BASE + 'lib/exe/fetch.php?media=' + imageName;
            if (imageoptions !== undefined) {
                imagemap.filenameWiki = imagemap.filenameWiki + '?' + imageoptions;
                var cleanoptions = imagemap.getOptions(imageoptions);
                imageName = imageName + '&' + cleanoptions;
            }
        } else if (imageoptions) {
            imageName = imageName + '?' + imageoptions;
        }
        imagemap.img.src = imageName;
        return true;
    };

    //import old imagemap entries --> allow to edit old imagemap entries
    this.parseInputImageMap = function(text) {
        var reg = /\{\{map>(.*?)(?:\?(.*?))?(?:\|)(.*?)\}\}/;
        var match = reg.exec(text[0]);
        var imageName = match[1];
        imagemap.filenameWiki = imageName;
        var imageoptions = match[2];

        var st = text[0];
        var timestamp = Number(new Date());

        if (st.replace(/.*\||\}/gi,'') === 0) {
            imagemap.imageID = 'Bild' + timestamp;
        } else {
            imagemap.imageID = st.replace(/.*\||\}/gi,'');
        }

        // the following chunk of code is copied from this.parseInput and should be refactored into a function
        if (!/http\:\/\/|ftp\:\/\//.test(imageName)) {
            //we have a local image

            if (/wiki\\?(.)dokuwiki-128.png/.exec(JSINFO.plugin_imagemap_mldummy)[1]=='/') {
                //namespaces are not divided by colon, but rewritten to be divided by slash:
                imageName = imageName.replace(/:/g, "/");
            }

            imageName = DOKU_BASE + 'lib/exe/fetch.php?media=' + imageName;
            if (imageoptions !== undefined) {
                imagemap.filenameWiki = imagemap.filenameWiki + '?' + imageoptions;
                var cleanoptions = imagemap.getOptions(imageoptions);
                imageName = imageName + '&' + cleanoptions;
            }
        } else if (imageoptions) {
            imageName = imageName + '?' + imageoptions;
        }
        imagemap.img.src = imageName;

        var scaleX = 1;
        var scaleY = 1;

        if (imagemap.setWidth !== undefined && imagemap.setWidth != '0'){
            scaleX = Number(imagemap.setWidth)/imagemap.img.width;
            scaleY = scaleX;
        } else if (imagemap.setHight !== undefined && imagemap.setHight != '0'){
            scaleY = Number(imagemap.setHight)/imagemap.img.height;
            scaleX = scaleY;
        }


        for (var i = 1; i < text.length-1; i++) {
            var regArea = new RegExp('.*\\[\\[(.*)\\|(.*)@(.*),(.*),(.*),(.*)\\]\\].*');
            var re = regArea.exec(text[i]);
            if (re === null) continue;
            var url = RegExp.$1;
            var desc = RegExp.$2;
            var x1 = Math.round(parseInt(RegExp.$3)/scaleX);
            var y1 = Math.round(parseInt(RegExp.$4)/scaleY);
            var x2 = Math.round(parseInt(RegExp.$5)/scaleX);
            var y2 = Math.round(parseInt(RegExp.$6)/scaleY);
            x2 = x2 - x1;
            y2 = y2 - y1;
            this.areas[this.areas.length] = new this.Item(x1, y1, x2, y2, url, desc);
            imagemap.drag.mode = imagemap.MODE_NEW;
            imagemap.myUp();
        }
        return (this.areas.length > 0);
    };

}
