class ImageMappingEditor {

    static COORDREGEX = /@\s*((\d+(,\s*)?)+)/;

    /** @var {string} URL to load the image of the map */
    imgurl = '';
    /** @var {string} prefix for the map syntax to change */
    header = '';
    /** @var {string} syntax to add the coordinates to */
    syntax = '';
    /** @var {string} suffix for the map syntax to change */
    footer = '';
    /** @var {selection_class} the selection in the editor that will be replaced*/
    selection = null;

    /** @var {array} the coordinates of the click area */
    coordinates = [];
    /** @var {SVGSVGElement} the editor SVG */
    svg = null;
    /** @var {int} width of the image */
    width = 0;
    /** @var {int} height of the image */
    height = 0;

    /** @var {JQuery} the dialog */
    $dialog = null;

    /**
     * @param {selection_class} selection
     */
    constructor(selection) {
        this.selection = selection;
        if (!this.initializeSyntaxData()) return;
        this.initializeSvg();
        this.showDialog();
    }


    /**
     * Initializes the editor values from the current selection
     *
     * @returns {boolean} true if the editor can be shown, false if the selection is invalid
     */
    initializeSyntaxData() {
        const area = this.selection.obj; // the editor text area

        // find the map syntax surrounding the selection
        const map = this.elementBoundary('{{map>', '{{<map}}');

        // the following code figures out if we are inside an image or an existing map, and if a link
        // inside the map is selected. It will then adjust the selection to mark the part of the syntax
        // that will be replaced when the editor is closed.
        // The selection will be replaced by header + syntax + footer, where the syntax will have the new
        // coordinates appended.

        if (!map) {
            // no map syntax found, check if we are inside an image
            const img = this.elementBoundary('{{', '}}');
            if (!img) {
                alert(LANG.plugins.imagemapping.wrongcontext);
                return false;
            }

            // we are inside an image, create a new map
            this.header = '{{map>' + area.value.substring(img.start, img.end) + "}}\n";
            this.footer = "\n{{<map}}";
            this.imgurl = this.constructImgUrl(area.value.substring(img.start, img.end));
            this.selection.start = img.start - 2;
            this.selection.end = img.end + 2;
            // syntax stays empty and will be filled on save
        } else {
            // we are inside an existing map
            this.imgurl = this.constructImgUrl(area.value.substring(map.start, area.value.indexOf('}}', map.start)));

            // check if a link is selected
            let link = this.elementBoundary('[[', ']]', map.start, map.end);
            if (link) {
                // we are in a link, adjust it if it's an image link
                const imglink = this.elementBoundary('{{', '}}', link.start, link.end);
                if (imglink) link = imglink;

                this.syntax = area.value.substring(link.start, link.end);
                this.selection.start = link.start;
                this.selection.end = link.end;

                // add title separator if needed
                if (this.syntax.indexOf('|') === -1) this.syntax += '|';

                // check for current coordinates
                const match = this.syntax.match(ImageMappingEditor.COORDREGEX);
                if (match) {
                    // we are in a link with coordinates, remember them and remove them from the syntax
                    this.coordinates = match[1].split(',').map((v) => parseInt(v, 10)).filter(Number);
                    this.syntax = this.syntax.replace(ImageMappingEditor.COORDREGEX, '');
                }
            }
        }

        DWsetSelection(this.selection);
        return true;
    }

    /**
     * Search around the current selection start for the boundaries of the wanted syntax element
     *
     * We care only for selection start, because the full selection might cross map boundaries
     * Returned are the indexes *inside* the element, excluding the open/close syntax.
     *
     * @param {string} open The opening syntax
     * @param {string} close The closing syntax
     * @param {string} [min] Lower boundary for the search
     * @param {string} [max] Upper boundary for the search
     * @returns {Object|false} false if not in the element, {start: int, end: int} if inside
     */
    elementBoundary(open, close, min, max) {
        const area = this.selection.obj; // the editor text area
        if (min === undefined) min = 0;
        if (max === undefined) max = area.value.length;

        // potential boundaries
        const start = area.value.lastIndexOf(open, this.selection.start);
        const end = area.value.indexOf(close, this.selection.start);

        // boundaries of the previous and next elements of the same type
        const prev = area.value.lastIndexOf(close, this.selection.start - close.length);
        const next = area.value.indexOf(open, this.selection.start + open.length);

        // out of bounds?
        if (start < min) return false;
        if (prev > -1 && prev > min && start < prev) return false;
        if (end > max) return false;
        if (next > -1 && next < end && end > next) return false;

        // still here? we are inside a boundary
        return {
            start: start + open.length,
            end: end
        };
    }

    /**
     * Creates the Editor SVG visualizing the current click area
     */
    initializeSvg() {
        // create an SVG element with the image as background
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

        const img = new Image();
        img.onload = function () {
            this.svg.setAttribute('viewBox', '0 0 ' + img.width + ' ' + img.height);

            // background image
            const image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            image.setAttribute('href', img.src);
            image.setAttribute('x', 0);
            image.setAttribute('y', 0);
            image.setAttribute('width', img.width);
            image.setAttribute('height', img.height);
            this.svg.appendChild(image);
            this.width = img.width;
            this.height = img.height;

            // group for the polygon
            const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            this.svg.appendChild(group);

            // initialize polgon, handles and drag handler
            this.initializeDragHandler();
            this.drawPolygon();
            this.initializeHandles();
        }.bind(this);
        img.src = this.imgurl;
    }

    /**
     * Display the editor dialog
     */
    showDialog() {
        this.$dialog = jQuery('<div></div>');
        this.$dialog.addClass('plugin-imagemapping');
        this.$dialog.append(this.svg);
        this.$dialog.dialog({
            title: LANG.plugins.imagemapping.title,
            width: Math.max(500, jQuery(window).width() * 0.75),
            height: Math.max(300, jQuery(window).height() * 0.75),
            modal: true,
            closeText: LANG.plugins.imagemapping.btn_cancel,
            buttons: [
                {
                    text: LANG.plugins.imagemapping.btn_fewer,
                    click: this.removePoint.bind(this),
                },
                {
                    text: LANG.plugins.imagemapping.btn_more,
                    click: this.addPoint.bind(this),
                },
                {
                    text: LANG.plugins.imagemapping.btn_save,
                    click: this.save.bind(this),
                },
                {
                    text: LANG.plugins.imagemapping.btn_cancel,
                    click: function () {
                        jQuery(this).dialog('close');
                    }
                }
            ]
        });
    }

    /**
     * Adds drag handles for all points of the current click area
     */
    initializeHandles() {
        // remove old handles
        Array.from(this.svg.querySelectorAll('circle.handle')).forEach((h) => h.remove());

        // for circles, we need to convert the center and radius to x/y coordinates
        let isCircle = false;
        let coords = this.coordinates;
        if (this.coordinates.length === 3) {
            coords = [
                this.coordinates[0], this.coordinates[1],
                this.coordinates[0] + this.coordinates[2], this.coordinates[1]
            ];
            isCircle = true;
        }

        // draw new handles
        for (let i = 0; i < coords.length; i += 2) {
            const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            handle.setAttribute('class', 'handle ' + (isCircle ? 'circle' : ''));
            handle.setAttribute('cx', coords[i]);
            handle.setAttribute('cy', coords[i + 1]);
            handle.setAttribute('r', 15);
            this.svg.appendChild(handle);
        }
    }

    /**
     * Update the internal coordinates array from the current SVG handle positions
     */
    updateCoordinates() {
        const handles = Array.from(this.svg.querySelectorAll('circle.handle'));

        if (handles.length === 2 && handles[0].classList.contains('circle')) {
            this.coordinates = [
                handles[0].getAttribute('cx'),
                handles[0].getAttribute('cy'),
                Math.sqrt(
                    Math.pow(handles[0].getAttribute('cx') - handles[1].getAttribute('cx'), 2) +
                    Math.pow(handles[0].getAttribute('cy') - handles[1].getAttribute('cy'), 2)
                )
            ];
        } else {
            this.coordinates = handles.map((h) => [h.getAttribute('cx'), h.getAttribute('cy')]).flat();
        }

        this.coordinates = this.coordinates.map((v) => parseInt(v, 10));
    }

    /**
     * Creates a polgon of the currently defined click area
     */
    drawPolygon() {
        // polgons go to a group that we clear first
        const group = this.svg.querySelector('g');
        group.innerHTML = '';

        // draw the polygon
        if (this.coordinates.length === 3) {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', this.coordinates[0]);
            circle.setAttribute('cy', this.coordinates[1]);
            circle.setAttribute('r', this.coordinates[2]);
            group.appendChild(circle);
        } else if (this.coordinates.length === 4) {
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', Math.min(this.coordinates[0], this.coordinates[2]));
            rect.setAttribute('y', Math.min(this.coordinates[1], this.coordinates[3]));
            rect.setAttribute('width', Math.abs(this.coordinates[2] - this.coordinates[0]));
            rect.setAttribute('height', Math.abs(this.coordinates[3] - this.coordinates[1]));
            group.appendChild(rect);
        } else if (this.coordinates.length > 4) {
            const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            polygon.setAttribute('points', this.coordinates.join(' '));
            group.appendChild(polygon);
        }

    }


    /**
     * Makes circles draggable
     *
     * @link https://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/
     */
    initializeDragHandler() {
        const svg = this.svg;
        svg.addEventListener('mousedown', startDrag);
        svg.addEventListener('mousemove', drag);
        svg.addEventListener('mouseup', endDrag);
        svg.addEventListener('mouseleave', endDrag);
        svg.addEventListener('touchstart', startDrag);
        svg.addEventListener('touchmove', drag);
        svg.addEventListener('touchend', endDrag);
        svg.addEventListener('touchleave', endDrag);
        svg.addEventListener('touchcancel', endDrag);

        let selectedElement, offset;
        const self = this;

        function getMousePosition(evt) {
            const CTM = svg.getScreenCTM();
            if (evt.touches) {
                evt = evt.touches[0];
            }
            return {
                x: (evt.clientX - CTM.e) / CTM.a,
                y: (evt.clientY - CTM.f) / CTM.d
            };
        }

        function startDrag(evt) {
            if (evt.target.nodeName !== 'circle') return;

            selectedElement = evt.target;
            offset = getMousePosition(evt);
            offset.x -= parseFloat(selectedElement.getAttributeNS(null, "cx"));
            offset.y -= parseFloat(selectedElement.getAttributeNS(null, "cy"));
        }

        function drag(evt) {
            if (!selectedElement) return;

            evt.preventDefault();
            const coord = getMousePosition(evt);
            selectedElement.setAttributeNS(null, "cx", coord.x - offset.x);
            selectedElement.setAttributeNS(null, "cy", coord.y - offset.y);

            self.updateCoordinates(svg);
            self.drawPolygon(svg, self.coords);
        }

        function endDrag() {
            selectedElement = null;
        }
    }

    /**
     * Adds a new point to the polygon
     */
    addPoint() {
        let c = this.coordinates;

        if (c.length < 3) {
            // add a centered circle
            c = [Math.ceil(this.width / 2), Math.ceil(this.height / 2), Math.ceil(this.width / 10)];
        } else if (c.length === 3) {
            // convert circle to rectangle
            c = [c[0], c[1], c[0] + c[2], c[1] + c[2]];
        } else {
            // add new point in the middle of the last two
            c = c.concat([
                Math.ceil(Math.abs(c[c.length - 4] - c[c.length - 2]) / 2) + Math.min(c[c.length - 4], c[c.length - 2]),
                Math.ceil(Math.abs(c[c.length - 3] - c[c.length - 1]) / 2) + Math.min(c[c.length - 3], c[c.length - 1])
            ]);
        }

        this.coordinates = c;
        this.initializeHandles();
        this.drawPolygon();
    }

    /**
     * Removes a point from the polygon
     */
    removePoint() {
        let c = this.coordinates;

        if (c.length < 4) {
            // remove all points
            c = [];
        } else if (c.length === 4) {
            // convert to circle
            c = [c[0], c[1], Math.abs(c[1] - c[2])];
        } else {
            // remove last point
            c = c.slice(0, -2);
        }

        this.coordinates = c;
        this.initializeHandles();
        this.drawPolygon();
    }

    /**
     * Saves the coordinates to the textarea
     */
    save() {
        if (this.syntax === '') {
            if (this.coordinates.length >= 3) {
                // we had no previous syntax, so we add a new dummy link
                this.header += "\n  * [[new link|title ";
                this.footer = "]]" + this.footer;
            }
        }

        let coords = '';
        if (this.coordinates.length >= 0) {
            coords = '@' + this.coordinates.join(',');
        }

        pasteText(
            this.selection,
            this.header + this.syntax + coords + this.footer,
            {
                // select the new coordinates
                startofs: (this.header + this.syntax).length,
                endofs: this.footer.length
            }
        );
        this.$dialog.dialog('close');
        this.$dialog.remove();
    }

    /**
     * Create the image URL from the image syntax
     *
     * @param {string} img Image syntax without the {{ and }}
     * @returns {string}
     */
    constructImgUrl(img) {
        let url = img.split('|')[0].split('?')[0];
        if (url.match(/^https?:\/\//)) {
            return url;
        }
        return DOKU_BASE + 'lib/exe/fetch.php?media=' + url;
    }

}
