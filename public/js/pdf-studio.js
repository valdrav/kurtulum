/**
 * PDF Studio — tarayıcı içi görsel PDF düzenleyici
 * PDF.js (görüntüleme) + Fabric.js (seçim/düzenleme) + pdf-lib (dışa aktarma)
 */
(function () {
    'use strict';

    const cfg = window.pdfStudioConfig || {};
    pdfjsLib.GlobalWorkerOptions.workerSrc = cfg.workerSrc;

    const state = {
        pdfDoc: null,
        pdfBytes: null,
        fileName: 'document.pdf',
        pageNum: 1,
        pageCount: 0,
        scale: 1.2,
        tool: 'select',
        pageLayers: new Map(),
        history: [],
        fabric: null,
        rendering: false,
        loadingLayer: false,
    };

    const el = {
        file: document.getElementById('pdfStudioFile'),
        exportBtn: document.getElementById('pdfStudioExport'),
        thumbList: document.getElementById('pdfStudioThumbList'),
        empty: document.getElementById('pdfStudioEmpty'),
        stage: document.getElementById('pdfStudioStage'),
        render: document.getElementById('pdfStudioRender'),
        fabricCanvas: document.getElementById('pdfStudioFabric'),
        wrap: document.getElementById('pdfStudioCanvasWrap'),
        pageIndicator: document.getElementById('pdfStudioPageIndicator'),
        color: document.getElementById('pdfStudioColor'),
        fontSize: document.getElementById('pdfStudioFontSize'),
        stampSelect: document.getElementById('pdfStudioStamp'),
        textEdit: document.getElementById('pdfStudioTextEdit'),
        textArea: document.getElementById('pdfStudioTextArea'),
        propsHint: document.getElementById('pdfStudioPropsHint'),
    };

    function pushHistory() {
        if (!state.fabric) return;
        state.history.push(JSON.stringify(state.fabric.toJSON(['data'])));
        if (state.history.length > 40) state.history.shift();
        document.getElementById('pdfStudioUndo').disabled = state.history.length === 0;
    }

    function saveCurrentPageLayer() {
        if (!state.fabric || !state.pageCount) return;
        state.pageLayers.set(state.pageNum, {
            json: state.fabric.toJSON(['data']),
            width: state.fabric.getWidth(),
            height: state.fabric.getHeight(),
        });
    }

    async function loadPageLayer(num) {
        if (!state.fabric) return;
        state.loadingLayer = true;
        state.fabric.clear();
        const layer = state.pageLayers.get(num);
        if (layer?.json) {
            await new Promise((resolve) => {
                state.fabric.loadFromJSON(layer.json, () => {
                    state.fabric.renderAll();
                    resolve();
                });
            });
        }
        state.loadingLayer = false;
        state.history = [];
        document.getElementById('pdfStudioUndo').disabled = true;
    }

    async function renderPage(num) {
        if (!state.pdfDoc || state.rendering) return;
        state.rendering = true;
        saveCurrentPageLayer();

        state.pageNum = num;
        el.pageIndicator.textContent = num + ' / ' + state.pageCount;

        const page = await state.pdfDoc.getPage(num);
        const viewport = page.getViewport({ scale: state.scale });
        const ctx = el.render.getContext('2d');

        el.render.width = viewport.width;
        el.render.height = viewport.height;
        el.fabricCanvas.width = viewport.width;
        el.fabricCanvas.height = viewport.height;
        el.wrap.style.width = viewport.width + 'px';
        el.wrap.style.height = viewport.height + 'px';

        if (!state.fabric) {
            state.fabric = new fabric.Canvas('pdfStudioFabric', {
                selection: true,
                preserveObjectStacking: true,
            });
            bindFabricEvents();
        } else {
            state.fabric.setWidth(viewport.width);
            state.fabric.setHeight(viewport.height);
        }

        await page.render({ canvasContext: ctx, viewport }).promise;
        await loadPageLayer(num);
        updateThumbActive();
        state.rendering = false;
    }

    function bindFabricEvents() {
        state.fabric.on('object:added', () => {
            if (state.loadingLayer) return;
            pushHistory();
            updateDeleteBtn();
        });
        state.fabric.on('object:modified', () => pushHistory());
        state.fabric.on('selection:created', onSelection);
        state.fabric.on('selection:updated', onSelection);
        state.fabric.on('selection:cleared', onSelectionCleared);

        state.fabric.on('mouse:down', (opt) => {
            if (state.tool === 'select') return;
            const pointer = state.fabric.getPointer(opt.e);
            if (state.tool === 'text') {
                addTextBox(pointer.x, pointer.y);
                setTool('select');
            } else if (state.tool === 'highlight') {
                addHighlight(pointer.x, pointer.y);
                setTool('select');
            } else if (state.tool === 'whiteout') {
                addWhiteout(pointer.x, pointer.y);
                setTool('select');
            } else if (state.tool === 'stamp') {
                addStamp(pointer.x, pointer.y, el.stampSelect.value);
                setTool('select');
            }
        });

        if (state.fabric.freeDrawingBrush) {
            state.fabric.freeDrawingBrush.width = 2;
            state.fabric.freeDrawingBrush.color = el.color.value;
        }
    }

    function onSelection() {
        const obj = state.fabric.getActiveObject();
        updateDeleteBtn();
        if (obj && (obj.type === 'i-text' || obj.type === 'textbox')) {
            el.textEdit.classList.remove('d-none');
            el.propsHint.classList.add('d-none');
            el.textArea.value = obj.text || '';
        } else {
            onSelectionCleared();
        }
    }

    function onSelectionCleared() {
        el.textEdit.classList.add('d-none');
        el.propsHint.classList.remove('d-none');
        updateDeleteBtn();
    }

    function updateDeleteBtn() {
        document.getElementById('pdfStudioDelete').disabled = !state.fabric || !state.fabric.getActiveObject();
    }

    function addTextBox(x, y) {
        const text = new fabric.Textbox(cfg.labels?.newText || 'Metin', {
            left: x,
            top: y,
            fontSize: parseInt(el.fontSize.value, 10) || 14,
            fill: el.color.value,
            fontFamily: 'Helvetica, Arial, sans-serif',
            editable: true,
            data: { type: 'text' },
        });
        state.fabric.add(text).setActiveObject(text);
    }

    function addHighlight(x, y) {
        const rect = new fabric.Rect({
            left: x,
            top: y,
            width: 180,
            height: 24,
            fill: 'rgba(255, 235, 59, 0.45)',
            stroke: 'rgba(255, 193, 7, 0.8)',
            strokeWidth: 1,
            data: { type: 'highlight' },
        });
        state.fabric.add(rect).setActiveObject(rect);
    }

    function addWhiteout(x, y) {
        const rect = new fabric.Rect({
            left: x,
            top: y,
            width: 160,
            height: 22,
            fill: '#ffffff',
            stroke: '#dddddd',
            strokeWidth: 1,
            data: { type: 'whiteout' },
        });
        state.fabric.add(rect).setActiveObject(rect);
    }

    function addStamp(x, y, stampText) {
        const stamp = new fabric.Text(stampText, {
            left: x,
            top: y,
            fontSize: 28,
            fontWeight: 'bold',
            fill: 'rgba(220, 38, 38, 0.55)',
            fontFamily: 'Helvetica, Arial, sans-serif',
            angle: -25,
            data: { type: 'stamp' },
        });
        state.fabric.add(stamp).setActiveObject(stamp);
    }

    function setTool(tool) {
        state.tool = tool;
        document.querySelectorAll('#pdfStudioToolbar [data-tool]').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.tool === tool);
        });
        el.stampSelect.classList.toggle('d-none', tool !== 'stamp');

        if (!state.fabric) return;
        state.fabric.isDrawingMode = tool === 'draw';
        if (tool === 'draw' && state.fabric.freeDrawingBrush) {
            state.fabric.freeDrawingBrush.color = el.color.value;
            state.fabric.freeDrawingBrush.width = 2;
        }
        if (tool === 'rect') {
            const rect = new fabric.Rect({
                left: 80,
                top: 80,
                width: 120,
                height: 80,
                fill: 'transparent',
                stroke: el.color.value,
                strokeWidth: 2,
                data: { type: 'rect' },
            });
            state.fabric.add(rect).setActiveObject(rect);
            setTool('select');
        }
    }

    async function buildThumbnails() {
        el.thumbList.innerHTML = '';
        for (let i = 1; i <= state.pageCount; i++) {
            const page = await state.pdfDoc.getPage(i);
            const vp = page.getViewport({ scale: 0.22 });
            const canvas = document.createElement('canvas');
            canvas.width = vp.width;
            canvas.height = vp.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pdf-studio-thumb';
            btn.dataset.page = String(i);
            btn.innerHTML = '<img src="' + canvas.toDataURL() + '" alt=""><span>' + i + '</span>';
            btn.addEventListener('click', () => renderPage(i));
            el.thumbList.appendChild(btn);
        }
        updateThumbActive();
    }

    function updateThumbActive() {
        el.thumbList.querySelectorAll('.pdf-studio-thumb').forEach((btn) => {
            btn.classList.toggle('active', parseInt(btn.dataset.page, 10) === state.pageNum);
        });
    }

    async function loadPdfFile(file) {
        state.fileName = file.name || 'document.pdf';
        state.pageLayers.clear();
        state.pdfBytes = await file.arrayBuffer();
        state.pdfDoc = await pdfjsLib.getDocument({ data: state.pdfBytes.slice(0) }).promise;
        state.pageCount = state.pdfDoc.numPages;
        state.pageNum = 1;

        el.empty.classList.add('d-none');
        el.stage.classList.remove('d-none');
        el.exportBtn.disabled = false;

        await buildThumbnails();
        await renderPage(1);
    }

    async function exportPdf() {
        if (!state.pdfBytes || !window.PDFLib) return;
        saveCurrentPageLayer();

        const { PDFDocument } = PDFLib;
        const pdfDoc = await PDFDocument.load(state.pdfBytes);
        const pages = pdfDoc.getPages();

        for (let i = 1; i <= state.pageCount; i++) {
            const layer = state.pageLayers.get(i);
            if (!layer?.json?.objects?.length) continue;

            const page = pages[i - 1];
            const { width, height } = page.getSize();
            const cw = layer.width || el.render.width;
            const ch = layer.height || el.render.height;

            const tempCanvas = new fabric.StaticCanvas(null, {
                width: cw,
                height: ch,
            });

            await new Promise((resolve) => {
                tempCanvas.loadFromJSON(layer.json, () => {
                    tempCanvas.renderAll();
                    resolve();
                });
            });

            const pngDataUrl = tempCanvas.toDataURL({ format: 'png' });
            const pngBytes = Uint8Array.from(atob(pngDataUrl.split(',')[1]), (c) => c.charCodeAt(0));
            const pngImage = await pdfDoc.embedPng(pngBytes);
            page.drawImage(pngImage, {
                x: 0,
                y: 0,
                width,
                height,
            });
            tempCanvas.dispose();
        }

        const bytes = await pdfDoc.save();
        const blob = new Blob([bytes], { type: 'application/pdf' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = state.fileName.replace(/\.pdf$/i, '') + '-duzenlenmis.pdf';
        a.click();
        URL.revokeObjectURL(a.href);
    }

    // Events
    el.file?.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (file) loadPdfFile(file);
    });

    el.exportBtn?.addEventListener('click', exportPdf);

    document.querySelectorAll('#pdfStudioToolbar [data-tool]').forEach((btn) => {
        btn.addEventListener('click', () => setTool(btn.dataset.tool));
    });

    document.getElementById('pdfStudioPrev')?.addEventListener('click', () => {
        if (state.pageNum > 1) renderPage(state.pageNum - 1);
    });
    document.getElementById('pdfStudioNext')?.addEventListener('click', () => {
        if (state.pageNum < state.pageCount) renderPage(state.pageNum + 1);
    });

    document.getElementById('pdfStudioZoomIn')?.addEventListener('click', () => {
        state.scale = Math.min(3, state.scale + 0.15);
        renderPage(state.pageNum);
    });
    document.getElementById('pdfStudioZoomOut')?.addEventListener('click', () => {
        state.scale = Math.max(0.5, state.scale - 0.15);
        renderPage(state.pageNum);
    });
    document.getElementById('pdfStudioZoomReset')?.addEventListener('click', () => {
        state.scale = 1.2;
        renderPage(state.pageNum);
    });

    document.getElementById('pdfStudioUndo')?.addEventListener('click', () => {
        if (!state.fabric || state.history.length === 0) return;
        state.history.pop();
        const prev = state.history[state.history.length - 1];
        if (prev) {
            state.fabric.loadFromJSON(prev, () => state.fabric.renderAll());
        } else {
            state.fabric.clear();
        }
        document.getElementById('pdfStudioUndo').disabled = state.history.length === 0;
    });

    document.getElementById('pdfStudioDelete')?.addEventListener('click', () => {
        const obj = state.fabric?.getActiveObject();
        if (obj) {
            state.fabric.remove(obj);
            pushHistory();
            onSelectionCleared();
        }
    });

    el.textArea?.addEventListener('input', () => {
        const obj = state.fabric?.getActiveObject();
        if (obj && (obj.type === 'i-text' || obj.type === 'textbox')) {
            obj.set('text', el.textArea.value);
            state.fabric.renderAll();
        }
    });

    el.color?.addEventListener('input', () => {
        const obj = state.fabric?.getActiveObject();
        if (obj && obj.type !== 'rect') obj.set('fill', el.color.value);
        if (state.fabric?.freeDrawingBrush) state.fabric.freeDrawingBrush.color = el.color.value;
    });

    el.fontSize?.addEventListener('input', () => {
        const obj = state.fabric?.getActiveObject();
        if (obj && (obj.type === 'i-text' || obj.type === 'textbox')) {
            obj.set('fontSize', parseInt(el.fontSize.value, 10) || 14);
            state.fabric.renderAll();
        }
    });

    // Drag & drop
    const viewport = document.getElementById('pdfStudioViewport');
    viewport?.addEventListener('dragover', (e) => { e.preventDefault(); viewport.classList.add('drag-over'); });
    viewport?.addEventListener('dragleave', () => viewport.classList.remove('drag-over'));
    viewport?.addEventListener('drop', (e) => {
        e.preventDefault();
        viewport.classList.remove('drag-over');
        const file = e.dataTransfer?.files?.[0];
        if (file && file.type === 'application/pdf') loadPdfFile(file);
    });
})();
