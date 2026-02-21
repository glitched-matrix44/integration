@extends(app('app.layout'))

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-start align-items-center mb-3">
    <h5 class="fs-6 text-muted mb-0">
        {{ $integration->name }}
        {!! $integration->supportedInt?->getMeta('icon') !!}
    </h5>
    <span class="badge badge-{{ strtolower($integration->status) }} ms-2">
        {{ ucfirst($integration->status) }}
    </span>
    <span class="badge bg-secondary ms-2">
        Versions: {{ $totalVersions }}
    </span>
</div>

<form method="POST" action="#" id="knobForm">
    @csrf
    <input type="hidden" name="yaml" id="yamlHidden" value="{{ old('yaml', $knob->knob) }}">

    <div class="card shadow-sm">

        {{-- Header: Tabs + Mode Toggle --}}
        <div class="card-header pb-0 d-flex justify-content-between align-items-end">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#uiTab">UI View</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#yamlTab">YAML</a>
                </li>
            </ul>

            <div class="mb-1 d-flex gap-2 align-items-center">
                <span id="validBadge"   class="badge bg-success d-none">✓ Valid</span>
                <span id="invalidBadge" class="badge bg-danger  d-none">✗ Invalid</span>
                <button type="button" id="editBtn" class="btn btn-sm btn-outline-primary"   onclick="KnobEditor.enableEdit()">✏️ Edit</button>
                <button type="button" id="viewBtn" class="btn btn-sm btn-outline-secondary d-none" onclick="KnobEditor.disableEdit()">👁 View</button>
            </div>
        </div>

        <div class="card-body tab-content p-0">

            {{-- ── UI TAB ── --}}
            <div class="tab-pane fade show active p-3" id="uiTab">
                {{-- View --}}
                <div id="uiViewMode">
                    <div id="yamlRendered" class="yaml-ui-view"></div>
                </div>
                {{-- Edit --}}
                <div id="uiEditMode" class="d-none">
                    <div id="yamlFormFields"></div>
                </div>
            </div>

            {{-- ── YAML TAB ── --}}
            <div class="tab-pane fade" id="yamlTab">
                {{-- View: syntax-highlighted pre --}}
                <div id="yamlViewMode" class="p-3">
                    <pre id="yamlHighlighted"
                         class="p-3 rounded bg-light m-0"
                         style="font-size:0.85rem;font-family:monospace;"></pre>
                </div>

                {{-- Edit: textarea with validation UI --}}
                <div id="yamlEditMode" class="d-none p-3">
                    <div class="position-relative">
                        <textarea
                            id="yamlEditor"
                            rows="28"
                            class="form-control font-monospace"
                            style="font-size:0.85rem;line-height:1.6;resize:vertical;white-space:pre;overflow-x:auto;tab-size:2;"
                            spellcheck="false"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                        >{{ old('yaml', $knob->knob) }}</textarea>
                        <span id="cursorPos"
                              class="position-absolute text-muted"
                              style="bottom:10px;right:14px;font-size:0.7rem;pointer-events:none;font-family:monospace;">
                            Ln 1, Col 1
                        </span>
                    </div>

                    {{-- Valid feedback --}}
                    <div id="yamlValidFeedback" class="d-none mt-2 p-2 rounded border border-success bg-success bg-opacity-10 text-success small d-flex align-items-center gap-2">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                        </svg>
                        Valid YAML
                    </div>

                    {{-- Error feedback --}}
                    <div id="yamlErrorFeedback" class="d-none mt-2 p-2 rounded border border-danger bg-danger bg-opacity-10 text-danger small">
                        <div class="d-flex align-items-start gap-2">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="mt-1 flex-shrink-0">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            <div>
                                <strong id="yamlErrorTitle">YAML Error</strong>
                                <div id="yamlErrorMsg" class="mt-1 font-monospace" style="font-size:0.78rem;white-space:pre-wrap;"></div>
                            </div>
                        </div>
                        <div id="yamlErrorLine" class="d-none mt-2 pt-2 border-top border-danger border-opacity-25">
                            <code id="yamlErrorLineContent"
                                  class="d-block p-2 bg-danger bg-opacity-10 rounded"
                                  style="font-size:0.78rem;white-space:pre;"></code>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <span id="footerError" class="text-danger small d-none">
                ⚠️ Fix YAML errors before saving.
            </span>
            <span></span>
            <button id="saveBtn" class="btn btn-primary d-none" type="submit">
                💾 Save New Version
            </button>
        </div>

    </div>
</form>

{{-- ── Styles ──────────────────────────────────────────── --}}
<style>
/* UI View */
.yaml-ui-view .yaml-section          { margin-bottom: 1.25rem; }
.yaml-ui-view .yaml-section-title    { font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6c757d;border-bottom:1px solid #dee2e6;padding-bottom:.25rem;margin-bottom:.75rem; }
.yaml-kv                              { display:flex;align-items:baseline;gap:.5rem;padding:.3rem 0;border-bottom:1px solid #f0f0f0; }
.yaml-kv:last-child                   { border-bottom:none; }
.yaml-key                             { font-size:.8rem;font-weight:600;color:#495057;min-width:160px;flex-shrink:0; }
.yaml-value                           { font-size:.85rem;color:#212529;word-break:break-all; }
.yaml-value.is-bool-true              { color:#198754;font-weight:600; }
.yaml-value.is-bool-false             { color:#dc3545;font-weight:600; }
.yaml-value.is-null                   { color:#adb5bd;font-style:italic; }
.yaml-value.is-number                 { color:#0d6efd; }

/* YAML highlight (view mode) */
#yamlHighlighted .y-key     { color:#d63384; }
#yamlHighlighted .y-str     { color:#198754; }
#yamlHighlighted .y-num     { color:#0d6efd; }
#yamlHighlighted .y-bool    { color:#fd7e14;font-weight:600; }
#yamlHighlighted .y-null    { color:#adb5bd;font-style:italic; }
#yamlHighlighted .y-comment { color:#6c757d;font-style:italic; }

/* Textarea validation state */
#yamlEditor.is-valid   { border-color:#198754 !important; box-shadow:0 0 0 .2rem rgba(25,135,84,.15) !important; }
#yamlEditor.is-invalid { border-color:#dc3545 !important; box-shadow:0 0 0 .2rem rgba(220,53,69,.15) !important; }
</style>

{{-- js-yaml (tiny, no conflicts with anything) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-yaml/4.1.0/js-yaml.min.js"></script>

{{-- ── App Logic ────────────────────────────────────────── --}}
<script>
const KnobEditor = (function () {

    // ── State
    let isEditMode  = false;
    let isYamlValid = true;
    let validTimer  = null;
    let lastFormYaml = null;

    // ── DOM refs (resolved lazily)
    const el = id => document.getElementById(id);

    // ════════════════════════════════════════════════════════
    // YAML VALIDATION
    // ════════════════════════════════════════════════════════
    function validateYaml(text) {
        try {
            jsyaml.load(text);
            setValid();
            return true;
        } catch (e) {
            setInvalid(e);
            return false;
        }
    }

    function setValid() {
        isYamlValid = true;

        el('yamlEditor')?.classList.replace('is-invalid', 'is-valid') ||
        el('yamlEditor')?.classList.add('is-valid');

        el('yamlValidFeedback')?.classList.remove('d-none');
        el('yamlErrorFeedback')?.classList.add('d-none');
        el('validBadge')?.classList.remove('d-none');
        el('invalidBadge')?.classList.add('d-none');
        el('footerError')?.classList.add('d-none');

        const btn = el('saveBtn');
        if (btn) { btn.disabled = false; btn.title = ''; }
    }

    function setInvalid(e) {
        isYamlValid = false;

        el('yamlEditor')?.classList.replace('is-valid', 'is-invalid') ||
        el('yamlEditor')?.classList.add('is-invalid');

        el('yamlValidFeedback')?.classList.add('d-none');
        el('yamlErrorFeedback')?.classList.remove('d-none');
        el('validBadge')?.classList.add('d-none');
        el('invalidBadge')?.classList.remove('d-none');
        el('footerError')?.classList.remove('d-none');

        const btn = el('saveBtn');
        if (btn) { btn.disabled = true; btn.title = 'Fix YAML errors first'; }

        // Error details
        const mark = e.mark ?? {};
        const ln   = (mark.line   ?? 0) + 1;
        const col  = (mark.column ?? 0) + 1;

        el('yamlErrorTitle').textContent = `YAML Error — Line ${ln}, Col ${col}`;
        el('yamlErrorMsg').textContent   = e.reason ?? e.message ?? 'Unknown error';

        const lines   = (el('yamlEditor')?.value ?? '').split('\n');
        const badLine = lines[(mark.line ?? 0)];

        if (badLine !== undefined) {
            const lineNum  = String(ln);
            const caretPad = ' '.repeat(lineNum.length + 3 + (mark.column ?? 0));
            el('yamlErrorLineContent').textContent = `${lineNum} │ ${badLine}\n${caretPad}^`;
            el('yamlErrorLine').classList.remove('d-none');
        } else {
            el('yamlErrorLine').classList.add('d-none');
        }
    }

    // ════════════════════════════════════════════════════════
    // TEXTAREA BEHAVIOUR
    // ════════════════════════════════════════════════════════
    function initTextarea() {
        const ta = el('yamlEditor');
        if (!ta) return;

        // Tab → 2 spaces (with multi-line indent support)
        ta.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            e.preventDefault();

            const start  = this.selectionStart;
            const end    = this.selectionEnd;
            const spaces = '  ';

            if (start !== end) {
                // Multi-line: indent/unindent each selected line
                const lines     = this.value.split('\n');
                let charCount   = 0;
                let newStart    = start;
                let newEnd      = end;

                for (let i = 0; i < lines.length; i++) {
                    const lineStart = charCount;
                    const lineEnd   = charCount + lines[i].length;

                    if (lineEnd >= start && lineStart <= end) {
                        if (e.shiftKey) {
                            const removed = lines[i].match(/^ {1,2}/)?.[0]?.length ?? 0;
                            lines[i] = lines[i].replace(/^ {1,2}/, '');
                            if (lineStart < start)  newStart = Math.max(start - removed, lineStart);
                            newEnd -= removed;
                        } else {
                            lines[i] = spaces + lines[i];
                            if (lineStart < start) newStart += spaces.length;
                            newEnd += spaces.length;
                        }
                    }
                    charCount += lines[i].length + 1;
                }
                this.value = lines.join('\n');
                this.selectionStart = newStart;
                this.selectionEnd   = newEnd;
            } else {
                this.value = this.value.substring(0, start) + spaces + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + spaces.length;
            }

            onTextareaChange();
        });

        // Cursor position indicator
        const updateCursor = () => {
            const before = ta.value.substring(0, ta.selectionStart).split('\n');
            el('cursorPos').textContent = `Ln ${before.length}, Col ${before[before.length - 1].length + 1}`;
        };
        ta.addEventListener('keyup',   updateCursor);
        ta.addEventListener('click',   updateCursor);
        ta.addEventListener('mouseup', updateCursor);

        // Live validation (debounced 300 ms)
        ta.addEventListener('input', () => {
            clearTimeout(validTimer);
            validTimer = setTimeout(onTextareaChange, 300);
        });

        // Run once immediately
        onTextareaChange();
    }

    function onTextareaChange() {
        const text = el('yamlEditor')?.value ?? '';
        el('yamlHidden').value = text;

        const valid = validateYaml(text);
        renderYamlHighlight(text);

        if (valid) {
            try {
                const parsed = jsyaml.load(text);
                renderUiView(parsed);
                if (isEditMode) renderUiForm(parsed, text);
            } catch (_) {}
        }
    }

    // ════════════════════════════════════════════════════════
    // UI VIEW — pretty key/value render
    // ════════════════════════════════════════════════════════
    function renderUiView(data) {
        el('yamlRendered').innerHTML = buildViewHtml(data, '');
    }

    function buildViewHtml(data, prefix) {
        if (!data || typeof data !== 'object' || Array.isArray(data)) return '';
        return Object.entries(data).map(([key, value]) => {
            const fullKey = prefix ? `${prefix}.${key}` : key;
            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                return `<div class="yaml-section">
                    <div class="yaml-section-title">${esc(fullKey)}</div>
                    ${buildViewHtml(value, fullKey)}
                </div>`;
            }
            return `<div class="yaml-kv">
                <span class="yaml-key">${esc(key)}</span>
                <span class="yaml-value ${valClass(value)}">${fmtValue(value)}</span>
            </div>`;
        }).join('');
    }

    // ════════════════════════════════════════════════════════
    // UI EDIT FORM — auto-generated inputs
    // ════════════════════════════════════════════════════════
    function renderUiForm(parsed, rawText) {
        if (rawText === lastFormYaml) return; // prevent loop when CM updates textarea
        el('yamlFormFields').innerHTML = buildFormHtml(parsed, '');
    }

    function buildFormHtml(data, prefix) {
        if (!data || typeof data !== 'object' || Array.isArray(data)) return '';
        return Object.entries(data).map(([key, value]) => {
            const fieldKey = prefix ? `${prefix}.${key}` : key;
            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                return `<fieldset class="border rounded p-3 mb-3">
                    <legend class="float-none w-auto px-2" style="font-size:.75rem;color:#6c757d;">${esc(fieldKey)}</legend>
                    ${buildFormHtml(value, fieldKey)}
                </fieldset>`;
            }
            const inputId = 'field_' + fieldKey.replace(/\./g, '_');
            return `<div class="mb-2 row align-items-center">
                <label for="${inputId}" class="col-sm-4 col-form-label col-form-label-sm text-end text-muted fw-semibold">${esc(key)}</label>
                <div class="col-sm-8">${buildInput(inputId, fieldKey, value)}</div>
            </div>`;
        }).join('');
    }

    function buildInput(id, fieldKey, value) {
        const upd = `KnobEditor.updateFromUi('${fieldKey}',`;
        if (typeof value === 'boolean') {
            return `<div class="form-check form-switch pt-1">
                <input class="form-check-input" type="checkbox" id="${id}" ${value ? 'checked' : ''}
                    onchange="${upd}this.checked)">
            </div>`;
        }
        if (typeof value === 'number') {
            return `<input type="number" id="${id}" class="form-control form-control-sm" value="${esc(value)}"
                oninput="${upd}parseFloat(this.value)||0)">`;
        }
        if (Array.isArray(value)) {
            return `<input type="text" id="${id}" class="form-control form-control-sm"
                value="${esc(value.join(', '))}" placeholder="Comma separated"
                oninput="${upd}this.value.split(',').map(s=>s.trim()))">`;
        }
        if (typeof value === 'string' && value.includes('\n')) {
            return `<textarea id="${id}" class="form-control form-control-sm" rows="3"
                oninput="${upd}this.value)">${esc(value)}</textarea>`;
        }
        return `<input type="text" id="${id}" class="form-control form-control-sm" value="${esc(value ?? '')}"
            oninput="${upd}this.value)">`;
    }

    // UI form → textarea sync
    function updateFromUi(dotPath, newValue) {
        try {
            const ta     = el('yamlEditor');
            const parsed = jsyaml.load(ta.value) || {};
            setNested(parsed, dotPath.split('.'), newValue);
            const newYaml = jsyaml.dump(parsed, { lineWidth: -1 });
            lastFormYaml  = newYaml;
            ta.value      = newYaml;
            el('yamlHidden').value = newYaml;
            validateYaml(newYaml);
            renderYamlHighlight(newYaml);
            renderUiView(jsyaml.load(newYaml));
        } catch (e) { console.warn('UI→textarea sync error', e); }
    }

    function setNested(obj, keys, value) {
        if (keys.length === 1) { obj[keys[0]] = value; return; }
        if (!obj[keys[0]] || typeof obj[keys[0]] !== 'object') obj[keys[0]] = {};
        setNested(obj[keys[0]], keys.slice(1), value);
    }

    // ════════════════════════════════════════════════════════
    // YAML SYNTAX HIGHLIGHT (view mode, read-only pre)
    // ════════════════════════════════════════════════════════
    function renderYamlHighlight(text) {
        const pre = el('yamlHighlighted');
        if (!pre) return;
        pre.innerHTML = text
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .split('\n').map(line => {
                if (/^\s*#/.test(line))
                    return `<span class="y-comment">${line}</span>`;
                return line.replace(/^(\s*)([\w\-]+)(\s*:\s*)(.*)$/, (_, sp, key, colon, val) =>
                    `${sp}<span class="y-key">${key}</span>${colon}${colorVal(val)}`
                );
            }).join('\n');
    }

    function colorVal(val) {
        const v = val.trim();
        if (!v) return '';
        if (v === 'true' || v === 'false') return `<span class="y-bool">${v}</span>`;
        if (v === 'null' || v === '~')     return `<span class="y-null">${v}</span>`;
        if (!isNaN(v))                     return `<span class="y-num">${v}</span>`;
        return `<span class="y-str">${v}</span>`;
    }

    // ════════════════════════════════════════════════════════
    // MODE TOGGLES
    // ════════════════════════════════════════════════════════
    function enableEdit() {
        isEditMode = true;
        el('uiViewMode').classList.add('d-none');
        el('uiEditMode').classList.remove('d-none');
        el('yamlViewMode').classList.add('d-none');
        el('yamlEditMode').classList.remove('d-none');
        el('saveBtn').classList.remove('d-none');
        el('editBtn').classList.add('d-none');
        el('viewBtn').classList.remove('d-none');

        initTextarea(); // safe to call multiple times (guarded inside)
        onTextareaChange(); // rerun so badges show correctly
    }

    function disableEdit() {
        isEditMode = false;
        el('uiViewMode').classList.remove('d-none');
        el('uiEditMode').classList.add('d-none');
        el('yamlViewMode').classList.remove('d-none');
        el('yamlEditMode').classList.add('d-none');
        el('saveBtn').classList.add('d-none');
        el('editBtn').classList.remove('d-none');
        el('viewBtn').classList.add('d-none');
        el('validBadge').classList.add('d-none');
        el('invalidBadge').classList.add('d-none');
        el('footerError').classList.add('d-none');

        // Reset textarea border
        el('yamlEditor')?.classList.remove('is-valid', 'is-invalid');
    }

    // ════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════
    function esc(v)     { return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function valClass(v){ if (v === null || v === undefined) return 'is-null'; if (typeof v === 'boolean') return v ? 'is-bool-true' : 'is-bool-false'; if (typeof v === 'number') return 'is-number'; return ''; }
    function fmtValue(v){ if (v === null || v === undefined) return 'null'; if (Array.isArray(v)) return esc(v.join(', ')); return esc(String(v)); }

    // ════════════════════════════════════════════════════════
    // INIT
    // ════════════════════════════════════════════════════════
    let textareaInited = false;
    const _initTextarea = initTextarea;
    initTextarea = function () {
        if (textareaInited) return;
        textareaInited = true;
        _initTextarea();
    };

    document.addEventListener('DOMContentLoaded', () => {
        const initial = el('yamlHidden').value;
        renderYamlHighlight(initial);
        try { renderUiView(jsyaml.load(initial)); } catch (_) {}
    });

    // Block submit if invalid
    document.addEventListener('DOMContentLoaded', () => {
        el('knobForm')?.addEventListener('submit', e => {
            if (!isYamlValid) { e.preventDefault(); el('footerError').classList.remove('d-none'); }
        });
    });

    // Public API
    return { enableEdit, disableEdit, updateFromUi };

})();
</script>

@endsection