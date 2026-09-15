/* =============================================================
   Summit Station — Image Viewer / Lightbox
   Dependency-free vanilla JS.

   Features:
   - Click to open lightbox (uses the original/highest-res src)
   - Zoom in / zoom out / reset buttons
   - Mouse wheel zoom (toward the cursor), Ctrl/Cmd not required
   - Drag & pan with grab / grabbing cursor
   - Double-click toggles zoom
   - Pinch-to-zoom + pan on touch devices
   - Escape, backdrop click, or X to close
   - Body scroll locked while open (no layout shift, no h-scroll)

   Usage:
   <img src="..." data-summit-zoom>
   or: SummitImageViewer.open(src, alt)

   Reusable on any product/booking page.
   ============================================================= */
(function () {
    'use strict';

    var MIN_SCALE = 1;
    var MAX_SCALE = 4;
    var BUTTON_STEP = 1.25;
    var WHEEL_STEP = 1.12;

    var state = {
        scale: 1,
        tx: 0,
        ty: 0,
        baseW: 0,
        baseH: 0,
        stageW: 0,
        stageH: 0,
        dragging: false,
        dragStart: null,
        pointers: {},
        pinchStartDist: 0,
        pinchStartScale: 1
    };

    var dom = null;
    var scrollBarWidth = 0;
    var previousFocus = null;

    function clamp(v, min, max) {
        return Math.min(Math.max(v, min), max);
    }

    function distance(a, b) {
        return Math.hypot(a.x - b.x, a.y - b.y);
    }

    function build() {
        var root = document.createElement('div');
        root.className = 'summit-viewer';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-label', 'Penampil gambar produk');

        var backdrop = document.createElement('div');
        backdrop.className = 'summit-viewer-backdrop';

        var stage = document.createElement('div');
        stage.className = 'summit-viewer-stage';
        stage.setAttribute('tabindex', '-1');

        var img = document.createElement('img');
        img.className = 'summit-viewer-img';
        img.alt = 'Gambar produk';
        img.draggable = false;

        var toolbar = document.createElement('div');
        toolbar.className = 'summit-viewer-toolbar';
        toolbar.innerHTML =
            '<button type="button" class="summit-viewer-btn" data-ssv="out" aria-label="Perkecil gambar">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">' +
                    '<circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line>' +
                    '<line x1="8" y1="11" x2="14" y2="11"></line>' +
                '</svg>' +
            '</button>' +
            '<span class="summit-viewer-zoomlabel" data-ssv="label" aria-live="polite">100%</span>' +
            '<button type="button" class="summit-viewer-btn" data-ssv="in" aria-label="Perbesar gambar">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">' +
                    '<circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line>' +
                    '<line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line>' +
                '</svg>' +
            '</button>' +
            '<button type="button" class="summit-viewer-btn is-reset" data-ssv="reset">Reset</button>';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'summit-viewer-close';
        closeBtn.setAttribute('aria-label', 'Tutup penampil gambar');
        closeBtn.innerHTML =
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">' +
                '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' +
            '</svg>';

        stage.appendChild(img);
        root.appendChild(backdrop);
        root.appendChild(stage);
        root.appendChild(toolbar);
        root.appendChild(closeBtn);
        document.body.appendChild(root);

        dom = {
            root: root,
            backdrop: backdrop,
            stage: stage,
            img: img,
            label: toolbar.querySelector('[data-ssv="label"]'),
            close: closeBtn
        };

        stage.addEventListener('pointerdown', onPointerDown);
        stage.addEventListener('pointermove', onPointerMove);
        stage.addEventListener('pointerup', onPointerUp);
        stage.addEventListener('pointercancel', onPointerUp);
        stage.addEventListener('dblclick', onDblClick);
        root.addEventListener('wheel', onWheel, { passive: false });

        root.addEventListener('click', function (e) {
            if (e.target === backdrop) close();
        });

        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            var action = btn.getAttribute('data-ssv');
            if (action === 'in') zoomTo(state.scale * BUTTON_STEP);
            if (action === 'out') zoomTo(state.scale / BUTTON_STEP);
            if (action === 'reset') resetZoom();
        });

        closeBtn.addEventListener('click', close);

        document.addEventListener('keydown', function (e) {
            if (!dom || !dom.root.classList.contains('is-open')) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
        });
    }

    function measure() {
        state.baseW = dom.img.getBoundingClientRect().width;
        state.baseH = dom.img.getBoundingClientRect().height;
        var sr = dom.stage.getBoundingClientRect();
        state.stageW = sr.width;
        state.stageH = sr.height;
        applyTransform();
    }

    function applyTransform() {
        var maxX = Math.max(0, (state.baseW * state.scale - state.stageW) / 2);
        var maxY = Math.max(0, (state.baseH * state.scale - state.stageH) / 2);
        state.tx = clamp(state.tx, -maxX, maxX);
        state.ty = clamp(state.ty, -maxY, maxY);
        dom.img.style.transform = 'translate(' + state.tx + 'px, ' + state.ty + 'px) scale(' + state.scale + ')';
        var zoomPct = Math.round(state.scale * 100) + '%';
        if (dom.label) dom.label.textContent = zoomPct;
    }

    function zoomTo(targetScale, anchorX, anchorY) {
        var next = clamp(targetScale, MIN_SCALE, MAX_SCALE);
        if (next === state.scale) return;

        var sr = dom.stage.getBoundingClientRect();
        var cx = sr.left + sr.width / 2;
        var cy = sr.top + sr.height / 2;
        var px = (typeof anchorX === 'number' && isFinite(anchorX)) ? anchorX : cx;
        var py = (typeof anchorY === 'number' && isFinite(anchorY)) ? anchorY : cy;
        var ratio = next / state.scale;

        state.tx = (px - cx) - ((px - cx) - state.tx) * ratio;
        state.ty = (py - cy) - ((py - cy) - state.ty) * ratio;
        state.scale = next;
        applyTransform();
    }

    function resetZoom() {
        state.scale = 1;
        state.tx = 0;
        state.ty = 0;
        applyTransform();
    }

    function onWheel(e) {
        if (!dom || !dom.root.classList.contains('is-open')) return;
        e.preventDefault();
        var factor = e.deltaY < 0 ? WHEEL_STEP : 1 / WHEEL_STEP;
        zoomTo(state.scale * factor, e.clientX, e.clientY);
    }

    function onDblClick(e) {
        e.preventDefault();
        zoomTo(state.scale > 1.5 ? 1 : 2.5, e.clientX, e.clientY);
    }

    function activePointers() {
        return Object.keys(state.pointers).map(function (k) {
            return state.pointers[k];
        });
    }

    function onPointerDown(e) {
        if (!dom || !dom.root.classList.contains('is-open')) return;
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        e.preventDefault();
        try { dom.stage.setPointerCapture(e.pointerId); } catch (err) {}

        state.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        var pts = activePointers();

        if (pts.length === 2) {
            state.dragging = false;
            dom.stage.classList.remove('is-dragging');
            state.pinchStartDist = distance(pts[0], pts[1]);
            state.pinchStartScale = state.scale;
        } else if (pts.length === 1) {
            state.dragging = true;
            state.dragStart = { x: e.clientX - state.tx, y: e.clientY - state.ty };
            dom.stage.classList.add('is-dragging');
        }
    }

    function onPointerMove(e) {
        if (!dom || !dom.root.classList.contains('is-open')) return;
        if (!(e.pointerId in state.pointers)) return;
        state.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        var pts = activePointers();

        if (pts.length === 2) {
            var d = distance(pts[0], pts[1]);
            if (state.pinchStartDist > 0 && d > 0) {
                var mx = (pts[0].x + pts[1].x) / 2;
                var my = (pts[0].y + pts[1].y) / 2;
                zoomTo(state.pinchStartScale * (d / state.pinchStartDist), mx, my);
            }
        } else if (state.dragging && state.dragStart) {
            state.tx = e.clientX - state.dragStart.x;
            state.ty = e.clientY - state.dragStart.y;
            applyTransform();
        }
    }

    function onPointerUp(e) {
        if (!dom) return;
        delete state.pointers[e.pointerId];
        try { dom.stage.releasePointerCapture(e.pointerId); } catch (err) {}

        var pts = activePointers();
        if (pts.length < 2) state.pinchStartDist = 0;

        if (pts.length === 1) {
            state.dragging = true;
            state.dragStart = { x: pts[0].x - state.tx, y: pts[0].y - state.ty };
        } else {
            state.dragging = false;
            state.dragStart = null;
            dom.stage.classList.remove('is-dragging');
        }
    }

    function open(src, alt) {
        if (!dom) build();
        if (dom.root.classList.contains('is-open')) return;

        previousFocus = document.activeElement;

        state.scale = 1;
        state.tx = 0;
        state.ty = 0;
        state.dragging = false;
        state.dragStart = null;
        state.pointers = {};
        state.pinchStartDist = 0;
        state.pinchStartScale = 1;

        dom.img.style.transform = 'none';
        dom.img.onload = measure;
        dom.img.src = src;
        dom.img.alt = alt || 'Gambar produk';

        if (dom.img.complete && dom.img.naturalWidth > 0) measure();

        lockScroll();
        dom.root.classList.add('is-open');
        try { dom.stage.focus({ preventScroll: true }); } catch (err) {}
    }

    function close() {
        if (!dom || !dom.root.classList.contains('is-open')) return;
        dom.root.classList.remove('is-open');
        dom.stage.classList.remove('is-dragging');
        state.pointers = {};
        state.dragging = false;
        unlockScroll();

        if (previousFocus && typeof previousFocus.focus === 'function') {
            try { previousFocus.focus({ preventScroll: true }); } catch (err) { previousFocus.focus(); }
        }
        previousFocus = null;
    }

    function lockScroll() {
        var body = document.body;
        scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
        if (scrollBarWidth > 0) body.style.paddingRight = scrollBarWidth + 'px';
        body.style.overflow = 'hidden';
    }

    function unlockScroll() {
        var body = document.body;
        body.style.overflow = '';
        body.style.paddingRight = '';
    }

    function init(scope) {
        var els = (scope || document).querySelectorAll('[data-summit-zoom]');
        Array.prototype.forEach.call(els, function (el) {
            if (el.__summitZoomBound) return;
            el.__summitZoomBound = true;
            el.addEventListener('click', function () {
                open(el.currentSrc || el.src || el.getAttribute('data-src'), el.alt);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }

    window.SummitImageViewer = { open: open, close: close, init: init };
})();