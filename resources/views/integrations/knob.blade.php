@extends(app('app.layout'))

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Knob', ($integration->name ?? 'Integration'), 'Woocommerce', 'Integration']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Knob page of Integration'))

@php
    $tabs = [
        [
            'route' => 'integration.show',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'far fa-fw fa-list-alt',
            'label' => 'Overview',
        ],
        [
            'route' => 'integration.configure',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-sliders-h',
            'label' => 'Configure',
        ],
        [
            'route' => 'integration.apiconf',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-screwdriver-wrench',
            'label' => 'Api Conf',
        ],
        [
            'route' => 'integration.knob',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-sliders',
            'label' => 'Knob',
        ],
        [
            'route' => 'integration.syncdata',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-rotate',
            'label' => 'Sync Data',
        ],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-start mb-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h5 class="fs-6 text-muted mb-0">
            {{ $integration->name }}
            {!! $integration->supportedInt?->getMeta('icon') !!}
        </h5>
        <x-userinterface::status :status="$integration->status" />
        <span class="small text-muted">Knob</span>
        <span id="knobStatusBadge" class="d-inline-flex align-items-center">
            <x-userinterface::status :status="$knobStatus" />
        </span>
        <span id="knobVersionBadge" class="badge text-bg-light border">v</span>
    </div>
    <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#versionHistoryModal">
        <i class="fas fa-clock-rotate-left"></i>
        <span>Version History</span>
    </button>
</div>

<form method="POST" action="#" id="knobForm">
    @csrf
    <input type="hidden" name="yaml" id="yamlHidden" value="">

    <div class="knob-panel">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <label for="knobTypeSelect" class="small text-muted mb-0">Knob Type</label>
                <select id="knobTypeSelect" class="form-select form-select-sm" style="width:220px;" disabled>
                    @foreach ($knobTypes as $knobType)
                        <option value="{{ $knobType }}" @selected($knobType === $defaultKnobType)>{{ $knobType }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Type switch disabled for now.</small>
            </div>

            <div class="mb-1 d-flex gap-2 align-items-center">
                <span id="invalidBadge" class="badge bg-danger d-none">Invalid</span>
                <button type="button" id="editBtn" class="btn btn-sm btn-outline-dark icon-btn" title="Edit" aria-label="Edit" onclick="KnobEditor.enableEdit()">
                    <i class="fas fa-pen"></i>
                </button>
                <button type="button" id="viewBtn" class="btn btn-sm btn-outline-secondary icon-btn d-none" title="View" aria-label="View" onclick="KnobEditor.disableEdit()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div>
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-3" data-bs-toggle="tab" data-bs-target="#uiTab" type="button" role="tab">UI View</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3" data-bs-toggle="tab" data-bs-target="#yamlTab" type="button" role="tab">YAML</button>
                </li>
            </ul>

            <div>
                <div id="yamlValidFeedback" class="d-none p-1 rounded border border-success bg-success bg-opacity-10 text-success small">
                    Valid YAML
                </div>

                <div id="yamlErrorFeedback" class="d-none p-1 rounded border border-danger bg-danger bg-opacity-10 text-danger small">
                    <strong id="yamlErrorTitle">YAML Error</strong>
                    <div id="yamlErrorMsg" class="mt-1 font-monospace" style="font-size:0.78rem;white-space:pre-wrap;"></div>
                    <div id="yamlErrorLine" class="d-none mt-2 pt-2 border-top border-danger border-opacity-25">
                        <code id="yamlErrorLineContent" class="d-block p-2 bg-danger bg-opacity-10 rounded" style="font-size:0.78rem;white-space:pre;"></code>
                    </div>
                </div>
            </div>

            <div class="tab-content">
            <div class="tab-pane fade show active p-1" id="uiTab">
                <div id="knobLoading" class="text-muted">Loading knob...</div>
                <div id="knobError" class="alert alert-danger d-none py-1 px-2 mb-2"></div>

                <div id="uiViewMode">
                    <div id="yamlRendered" class="yaml-ui-view"></div>
                </div>
                <div id="uiEditMode" class="d-none">
                    <div id="yamlFormFields"></div>
                </div>
            </div>

            <div class="tab-pane fade p-1" id="yamlTab">
                <div id="yamlViewMode">
                    <pre id="yamlHighlighted" class="p-2 rounded bg-light m-0 yaml-pre font-monospace yaml-highlight"></pre>
                </div>

                <div id="yamlEditMode" class="d-none mt-2">
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
                        ></textarea>
                        <span id="cursorPos" class="position-absolute text-muted" style="bottom:10px;right:14px;font-size:0.7rem;pointer-events:none;font-family:monospace;">
                            Ln 1, Col 1
                        </span>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center py-2">
            <span id="footerError" class="text-danger small d-none">Fix YAML errors before saving.</span>
            <button id="saveBtn" class="btn btn-sm btn-outline-primary d-none" type="submit">Save New Version</button>
        </div>
    </div>
</form>

<div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-labelledby="versionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="versionHistoryModalLabel">Knob Version History</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="versionCards" class="d-grid gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.yaml-ui-view .yaml-section-title {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
    border-bottom: 1px solid var(--bs-border-color);
    margin-bottom: .5rem;
    padding-bottom: .2rem;
}
.yaml-key { min-width: 180px; }
.yaml-pre {
    font-size: .82rem;
    max-height: 70vh;
    overflow: auto;
}
.yaml-highlight .y-key { color: var(--bs-pink, var(--bs-primary)); }
.yaml-highlight .y-str { color: var(--bs-success); }
.yaml-highlight .y-num { color: var(--bs-primary); }
.yaml-highlight .y-bool { color: var(--bs-warning); font-weight: 600; }
.yaml-highlight .y-null { color: var(--bs-secondary-color); font-style: italic; }
.yaml-highlight .y-comment { color: var(--bs-secondary-color); font-style: italic; }
.yaml-highlight .y-tag-new { color: var(--bs-success); font-size: .72rem; font-weight: 700; margin-left: .45rem; }
.yaml-highlight .y-tag-upd { color: var(--bs-warning-text-emphasis, var(--bs-warning)); font-size: .72rem; font-weight: 700; margin-left: .45rem; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/js-yaml/4.1.0/js-yaml.min.js"></script>
<script>
const KnobEditor = (() => {
    const integrationUid = @json($integrationUid);
    const knobDataUrl = @json(route('integration.knob.data', ['integrationUid' => $integrationUid]));
    const defaultKnobType = @json($defaultKnobType);

    let isEditMode = false;
    let isYamlValid = true;
    let validTimer = null;
    let textareaInited = false;
    let lastFormYaml = null;
    let initialYamlObject = null;
    let hasKnobData = true;
    const newFieldPaths = new Set();
    const updatedFieldPaths = new Set();

    const el = (id) => document.getElementById(id);

    function esc(v) {
        return String(v ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function deepClone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    function hasPath(obj, pathKeys) {
        let cur = obj;
        for (const key of pathKeys) {
            if (!cur || typeof cur !== 'object' || !(key in cur)) return false;
            cur = cur[key];
        }
        return true;
    }

    function resetFieldMarkers() {
        newFieldPaths.clear();
        updatedFieldPaths.clear();
    }

    function collectDiffs(initialObj, currentObj, prefix = '') {
        if (!currentObj || typeof currentObj !== 'object' || Array.isArray(currentObj)) return;

        Object.entries(currentObj).forEach(([key, currentValue]) => {
            const path = prefix ? `${prefix}.${key}` : key;
            const hasInitial = initialObj && typeof initialObj === 'object' && Object.prototype.hasOwnProperty.call(initialObj, key);

            if (!hasInitial) {
                newFieldPaths.add(path);
                return;
            }

            const initialValue = initialObj[key];
            const bothObjects =
                initialValue !== null &&
                currentValue !== null &&
                typeof initialValue === 'object' &&
                typeof currentValue === 'object' &&
                !Array.isArray(initialValue) &&
                !Array.isArray(currentValue);

            if (bothObjects) {
                collectDiffs(initialValue, currentValue, path);
                return;
            }

            if (JSON.stringify(initialValue) !== JSON.stringify(currentValue)) {
                updatedFieldPaths.add(path);
            }
        });
    }

    function recomputeMarkersFromCurrent(currentParsed) {
        resetFieldMarkers();
        if (!currentParsed || typeof currentParsed !== 'object' || Array.isArray(currentParsed)) return;
        collectDiffs(initialYamlObject || {}, currentParsed, '');
    }

    function extractLinePathsFromYaml(text) {
        const lines = text.split('\n');
        const linePaths = {};
        const stack = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const match = line.match(/^(\s*)([A-Za-z0-9_-]+)\s*:\s*(.*)$/);
            if (!match) continue;

            const indent = match[1].length;
            const key = match[2];
            const rhs = (match[3] || '').trim();

            while (stack.length && stack[stack.length - 1].indent >= indent) {
                stack.pop();
            }

            const parent = stack.length ? stack[stack.length - 1].path : '';
            const path = parent ? `${parent}.${key}` : key;
            linePaths[i] = path;

            if (rhs === '') {
                stack.push({ indent, path });
            }
        }
        return linePaths;
    }

    function changeBadge(path) {
        if (newFieldPaths.has(path)) {
            return '<span class="badge text-bg-success ms-2">New</span>';
        }
        if (updatedFieldPaths.has(path)) {
            return '<span class="badge text-bg-warning ms-2">Updated</span>';
        }
        return '';
    }

    function valClass(v) {
        if (v === null || v === undefined) return 'text-muted fst-italic';
        if (typeof v === 'boolean') return v ? 'text-success fw-semibold' : 'text-danger fw-semibold';
        if (typeof v === 'number') return 'text-primary';
        return '';
    }

    function humanizeKey(key) {
        return String(key)
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, (ch) => ch.toUpperCase());
    }

    function fmtValue(v) {
        if (v === null || v === undefined) return 'null';
        if (Array.isArray(v)) return esc(v.join(', '));
        if (typeof v === 'object') return esc(JSON.stringify(v));
        return esc(String(v));
    }

    function setError(message) {
        el('knobLoading')?.classList.add('d-none');
        const errorEl = el('knobError');
        errorEl?.classList.remove('d-none');
        if (errorEl) errorEl.textContent = message;
    }

    function renderCreatePrompt() {
        const rendered = el('yamlRendered');
        if (!rendered) return;

        rendered.innerHTML = `
            <div class="border rounded p-4 bg-body-tertiary text-center">
                <div class="fw-semibold mb-2">No knob exists for this integration yet.</div>
                <div class="text-muted small mb-3">Create a new knob and start editing it in UI View or YAML.</div>
                <button type="button" class="btn btn-sm btn-primary" onclick="KnobEditor.startCreate()">
                    Create Knob
                </button>
            </div>
        `;
    }

    function syncToggleButtons() {
        const editBtn = el('editBtn');
        const viewBtn = el('viewBtn');

        if (!hasKnobData) {
            editBtn?.classList.add('d-none');
            viewBtn?.classList.add('d-none');
            return;
        }

        if (isEditMode) {
            editBtn?.classList.add('d-none');
            viewBtn?.classList.remove('d-none');
        } else {
            editBtn?.classList.remove('d-none');
            viewBtn?.classList.add('d-none');
        }
    }

    function setValid() {
        isYamlValid = true;

        const editor = el('yamlEditor');
        editor?.classList.remove('is-invalid');
        editor?.classList.add('is-valid');

        el('yamlValidFeedback')?.classList.add('d-none');
        el('yamlErrorFeedback')?.classList.add('d-none');
        el('invalidBadge')?.classList.add('d-none');
        el('footerError')?.classList.add('d-none');

        const btn = el('saveBtn');
        if (btn) btn.disabled = false;
    }

    function setInvalid(e) {
        isYamlValid = false;

        const editor = el('yamlEditor');
        editor?.classList.remove('is-valid');
        editor?.classList.add('is-invalid');

        el('yamlValidFeedback')?.classList.add('d-none');
        el('yamlErrorFeedback')?.classList.remove('d-none');
        el('invalidBadge')?.classList.remove('d-none');
        el('footerError')?.classList.remove('d-none');

        const btn = el('saveBtn');
        if (btn) btn.disabled = true;

        const mark = e.mark ?? {};
        const ln = (mark.line ?? 0) + 1;
        const col = (mark.column ?? 0) + 1;

        if (el('yamlErrorTitle')) el('yamlErrorTitle').textContent = `YAML Error - Line ${ln}, Col ${col}`;
        if (el('yamlErrorMsg')) el('yamlErrorMsg').textContent = e.reason ?? e.message ?? 'Unknown error';

        const lines = (el('yamlEditor')?.value ?? '').split('\n');
        const badLine = lines[(mark.line ?? 0)];

        if (badLine !== undefined) {
            const lineNum = String(ln);
            const caretPad = ' '.repeat(lineNum.length + 3 + (mark.column ?? 0));
            if (el('yamlErrorLineContent')) {
                el('yamlErrorLineContent').textContent = `${lineNum} | ${badLine}\n${caretPad}^`;
            }
            el('yamlErrorLine')?.classList.remove('d-none');
        } else {
            el('yamlErrorLine')?.classList.add('d-none');
        }
    }

    function validateYaml(text) {
        try {
            if (!text.trim()) {
                setValid();
                return true;
            }

            jsyaml.load(text);
            setValid();
            return true;
        } catch (e) {
            setInvalid(e);
            return false;
        }
    }

    function renderYamlHighlight(text) {
        const pre = el('yamlHighlighted');
        if (!pre) return;

        if (!text.trim()) {
            pre.innerHTML = '';
            return;
        }

        const linePaths = extractLinePathsFromYaml(text);

        pre.innerHTML = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .split('\n')
            .map((line, index) => {
                if (/^\s*#/.test(line)) return `<span class="y-comment">${line}</span>`;
                const base = line.replace(/^(\s*)([\w\-]+)(\s*:\s*)(.*)$/, (_, sp, key, colon, val) => {
                    return `${sp}<span class="y-key">${key}</span>${colon}${colorVal(val)}`;
                });
                const path = linePaths[index];
                if (path && newFieldPaths.has(path)) return `${base}<span class="y-tag-new">[New]</span>`;
                if (path && updatedFieldPaths.has(path)) return `${base}<span class="y-tag-upd">[Updated]</span>`;
                return base;
            })
            .join('\n');
    }

    function colorVal(val) {
        const v = val.trim();
        if (!v) return '';
        if (v === 'true' || v === 'false') return `<span class="y-bool">${v}</span>`;
        if (v === 'null' || v === '~') return `<span class="y-null">${v}</span>`;
        if (!isNaN(v)) return `<span class="y-num">${v}</span>`;
        return `<span class="y-str">${v}</span>`;
    }

    function buildPrimitiveRow(key, value, fieldPath) {
        return `<div class="d-flex align-items-baseline gap-2 py-1 border-bottom">
            <span class="yaml-key small fw-semibold text-body-secondary">${esc(humanizeKey(key))}${changeBadge(fieldPath)}</span>
            <span class="small text-body ${valClass(value)}">${fmtValue(value)}</span>
        </div>`;
    }

    function buildArrayRow(key, value, fieldPath) {
        const items = value.length
            ? value.map((item) => `<span class="badge text-bg-light border">${esc(String(item))}</span>`).join(' ')
            : '<span class="small text-muted">Empty array</span>';

        return `<div class="py-1 border-bottom">
            <div class="small fw-semibold text-body-secondary mb-1">${esc(humanizeKey(key))}${changeBadge(fieldPath)}</div>
            <div class="d-flex flex-wrap gap-1">${items}</div>
        </div>`;
    }

    function buildSectionContent(data, prefix = '') {
        if (!data || typeof data !== 'object' || Array.isArray(data)) return '';

        return Object.entries(data).map(([key, value]) => {
            const fieldPath = prefix ? `${prefix}.${key}` : key;
            if (Array.isArray(value)) return buildArrayRow(key, value, fieldPath);
            if (value !== null && typeof value === 'object') {
                return `<div class="border rounded p-2 mb-2">
                    <div class="small fw-semibold text-body-secondary mb-2">${esc(humanizeKey(key))}${changeBadge(fieldPath)}</div>
                    ${buildSectionContent(value, fieldPath)}
                </div>`;
            }
            return buildPrimitiveRow(key, value, fieldPath);
        }).join('');
    }

    function renderUiView(data) {
        const rendered = el('yamlRendered');
        if (!rendered) return;

        if (!data || typeof data !== 'object' || Array.isArray(data)) {
            rendered.innerHTML = '<div class="small text-muted">No structured data available.</div>';
            return;
        }

        const sections = Object.entries(data).map(([key, value]) => {
            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                return `<section class="border rounded p-3 mb-3">
                    <div class="yaml-section-title">${esc(humanizeKey(key))}${changeBadge(key)}</div>
                    ${buildSectionContent(value, key)}
                </section>`;
            }

            return `<section class="border rounded p-3 mb-3">
                <div class="yaml-section-title">${esc(humanizeKey(key))}${changeBadge(key)}</div>
                ${Array.isArray(value) ? buildArrayRow(key, value, key) : buildPrimitiveRow(key, value, key)}
            </section>`;
        }).join('');

        rendered.innerHTML = sections;
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

    function sectionDomKey(sectionPath) {
        const encoded = encodeURIComponent(sectionPath || 'root');
        return encoded.replace(/%/g, '_');
    }

    function buildSectionAddControls(sectionPath) {
        const domKey = sectionDomKey(sectionPath);
        const encodedPath = encodeURIComponent(sectionPath || '');
        const sectionTitle = sectionPath ? humanizeKey(sectionPath) : 'Root';

        return `<div class="border rounded p-2 mt-3 bg-body-tertiary">
            <div class="small fw-semibold text-muted mb-2">Add Field In ${esc(sectionTitle)}</div>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="newFieldKey_${domKey}" class="form-label form-label-sm mb-1">Field Name</label>
                    <input id="newFieldKey_${domKey}" type="text" class="form-control form-control-sm" placeholder="example: currency">
                </div>
                <div class="col-md-3">
                    <label for="newFieldType_${domKey}" class="form-label form-label-sm mb-1">Type</label>
                    <select id="newFieldType_${domKey}" class="form-select form-select-sm">
                        <option value="string">String</option>
                        <option value="number">Number</option>
                        <option value="boolean">Boolean</option>
                        <option value="array">Array (comma)</option>
                        <option value="object">Object ({})</option>
                        <option value="null">Null</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="newFieldValue_${domKey}" class="form-label form-label-sm mb-1">Value</label>
                    <input id="newFieldValue_${domKey}" type="text" class="form-control form-control-sm" placeholder="value">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="button" class="btn btn-sm btn-primary" onclick="KnobEditor.addFieldToSection('${encodedPath}')">Add</button>
                </div>
            </div>
            <div id="addFieldFeedback_${domKey}" class="small mt-2 d-none"></div>
        </div>`;
    }

    function buildFormHtml(data, prefix = '') {
        if (!data || typeof data !== 'object' || Array.isArray(data)) return '';

        const content = Object.entries(data).map(([key, value]) => {
            const fieldKey = prefix ? `${prefix}.${key}` : key;

            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                return `<fieldset class="border rounded p-3 mb-3">
                    <legend class="float-none w-auto px-2 small text-muted">${esc(humanizeKey(fieldKey))}${changeBadge(fieldKey)}</legend>
                    ${buildFormHtml(value, fieldKey)}
                    ${buildSectionAddControls(fieldKey)}
                </fieldset>`;
            }

            const inputId = 'field_' + fieldKey.replace(/\./g, '_');
            return `<div class="mb-2 row align-items-center">
                <label for="${inputId}" class="col-sm-4 col-form-label col-form-label-sm text-end text-muted fw-semibold">${esc(humanizeKey(key))}${changeBadge(fieldKey)}</label>
                <div class="col-sm-8">${buildInput(inputId, fieldKey, value)}</div>
            </div>`;
        }).join('');

        return prefix ? content : `${content}${buildSectionAddControls('')}`;
    }

    function renderUiForm(parsed, rawText) {
        const fields = el('yamlFormFields');
        if (fields) fields.innerHTML = buildFormHtml(parsed, '');
    }

    function setNested(obj, keys, value) {
        if (keys.length === 1) {
            obj[keys[0]] = value;
            return;
        }

        if (!obj[keys[0]] || typeof obj[keys[0]] !== 'object') obj[keys[0]] = {};
        setNested(obj[keys[0]], keys.slice(1), value);
    }

    function updateFromUi(dotPath, newValue) {
        try {
            const ta = el('yamlEditor');
            if (!ta) return;

            const parsed = jsyaml.load(ta.value) || {};
            setNested(parsed, dotPath.split('.'), newValue);
            recomputeMarkersFromCurrent(parsed);

            const newYaml = jsyaml.dump(parsed, { lineWidth: -1 });
            lastFormYaml = newYaml;
            ta.value = newYaml;
            if (el('yamlHidden')) el('yamlHidden').value = newYaml;

            validateYaml(newYaml);
            renderYamlHighlight(newYaml);
            renderUiView(jsyaml.load(newYaml));
            if (isEditMode) {
                renderUiForm(parsed, newYaml);
            }
        } catch (_) {
        }
    }

    function parseAddedFieldValue(type, rawValue) {
        switch (type) {
            case 'number':
                return rawValue === '' ? 0 : Number(rawValue);
            case 'boolean':
                return String(rawValue).toLowerCase() === 'true';
            case 'array':
                return rawValue
                    .split(',')
                    .map((item) => item.trim())
                    .filter((item) => item !== '');
            case 'object':
                return {};
            case 'null':
                return null;
            case 'string':
            default:
                return rawValue;
        }
    }

    function setAddFieldFeedback(sectionPath, message, isError = false) {
        const box = el(`addFieldFeedback_${sectionDomKey(sectionPath)}`);
        if (!box) return;
        box.classList.remove('d-none', 'text-danger', 'text-success');
        box.classList.add(isError ? 'text-danger' : 'text-success');
        box.textContent = message;
    }

    function addFieldToSection(encodedSectionPath) {
        try {
            const sectionPath = decodeURIComponent(encodedSectionPath || '');
            const domKey = sectionDomKey(sectionPath);
            const keyInput = el(`newFieldKey_${domKey}`);
            const typeInput = el(`newFieldType_${domKey}`);
            const valueInput = el(`newFieldValue_${domKey}`);
            const ta = el('yamlEditor');

            if (!keyInput || !typeInput || !valueInput || !ta) return;

            const keyName = keyInput.value.trim();
            if (!keyName) {
                setAddFieldFeedback(sectionPath, 'Field name is required.', true);
                return;
            }

            const parsed = jsyaml.load(ta.value) || {};
            const sectionKeys = sectionPath ? sectionPath.split('.').filter(Boolean) : [];

            let target = parsed;
            for (const k of sectionKeys) {
                if (!target[k] || typeof target[k] !== 'object' || Array.isArray(target[k])) {
                    target[k] = {};
                }
                target = target[k];
            }

            if (Object.prototype.hasOwnProperty.call(target, keyName)) {
                setAddFieldFeedback(sectionPath, `Field "${keyName}" already exists. Use another field name.`, true);
                return;
            }
            target[keyName] = parseAddedFieldValue(typeInput.value, valueInput.value);
            recomputeMarkersFromCurrent(parsed);

            const newYaml = jsyaml.dump(parsed, { lineWidth: -1 });
            lastFormYaml = newYaml;
            ta.value = newYaml;
            if (el('yamlHidden')) el('yamlHidden').value = newYaml;

            validateYaml(newYaml);
            renderYamlHighlight(newYaml);
            renderUiView(parsed);
            if (isEditMode) renderUiForm(parsed, newYaml);

            keyInput.value = '';
            valueInput.value = '';
            setAddFieldFeedback(sectionPath, `Added field "${keyName}"`);
        } catch (e) {
            const sectionPath = decodeURIComponent(encodedSectionPath || '');
            setAddFieldFeedback(sectionPath, e?.message || 'Unable to add field.', true);
        }
    }

    function onTextareaChange() {
        const text = el('yamlEditor')?.value ?? '';
        if (el('yamlHidden')) el('yamlHidden').value = text;

        const valid = validateYaml(text);
        renderYamlHighlight(text);

        if (valid) {
            try {
                const parsed = text.trim() ? (jsyaml.load(text) || {}) : {};
                recomputeMarkersFromCurrent(parsed);
                renderYamlHighlight(text);
                renderUiView(parsed);
                if (isEditMode) renderUiForm(parsed, text);
            } catch (_) {
            }
        }
    }

    function initTextarea() {
        if (textareaInited) return;
        textareaInited = true;

        const ta = el('yamlEditor');
        if (!ta) return;

        ta.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            e.preventDefault();

            const start = this.selectionStart;
            const end = this.selectionEnd;
            const spaces = '  ';

            if (start !== end) {
                const lines = this.value.split('\n');
                let charCount = 0;
                let newStart = start;
                let newEnd = end;

                for (let i = 0; i < lines.length; i++) {
                    const lineStart = charCount;
                    const lineEnd = charCount + lines[i].length;

                    if (lineEnd >= start && lineStart <= end) {
                        if (e.shiftKey) {
                            const removed = lines[i].match(/^ {1,2}/)?.[0]?.length ?? 0;
                            lines[i] = lines[i].replace(/^ {1,2}/, '');
                            if (lineStart < start) newStart = Math.max(start - removed, lineStart);
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
                this.selectionEnd = newEnd;
            } else {
                this.value = this.value.substring(0, start) + spaces + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + spaces.length;
            }

            onTextareaChange();
        });

        const updateCursor = () => {
            const before = ta.value.substring(0, ta.selectionStart).split('\n');
            if (el('cursorPos')) {
                el('cursorPos').textContent = `Ln ${before.length}, Col ${before[before.length - 1].length + 1}`;
            }
        };

        ta.addEventListener('keyup', updateCursor);
        ta.addEventListener('click', updateCursor);
        ta.addEventListener('mouseup', updateCursor);

        ta.addEventListener('input', () => {
            clearTimeout(validTimer);
            validTimer = setTimeout(onTextareaChange, 300);
        });
    }

    function updateBadges(status, version) {
        const knobStatus = (status || 'unknown').toString().trim().toLowerCase();
        const knobStatusText = knobStatus
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (ch) => ch.toUpperCase());

        const statusWrap = el('knobStatusBadge');
        const badgeEl = statusWrap?.querySelector('.badge');
        if (badgeEl) {
            badgeEl.textContent = knobStatusText;
            Array.from(badgeEl.classList).forEach((cls) => {
                if (cls.startsWith('text-bg-')) {
                    badgeEl.classList.remove(cls);
                }
            });
            badgeEl.classList.add(`text-bg-${knobStatus}`);
        }

        if (el('knobVersionBadge')) el('knobVersionBadge').textContent = `v${version ?? '--'}`;
    }

    function setYamlContent(yamlText) {
        if (el('yamlHidden')) el('yamlHidden').value = yamlText;
        if (el('yamlEditor')) el('yamlEditor').value = yamlText;
        resetFieldMarkers();

        renderYamlHighlight(yamlText);

        try {
            const parsed = yamlText.trim() ? jsyaml.load(yamlText) : {};
            initialYamlObject = deepClone(parsed || {});
            renderUiView(parsed);
        } catch (_) {
            initialYamlObject = {};
            const rendered = el('yamlRendered');
            if (rendered) rendered.innerHTML = '<div class="alert alert-warning mb-0">Unable to parse YAML for UI view.</div>';
        }
    }

    function seedEmptyKnob() {
        const emptyYaml = '';
        hasKnobData = false;
        el('knobLoading')?.classList.add('d-none');
        el('knobError')?.classList.add('d-none');
        updateBadges('draft', '--');
        setYamlContent(emptyYaml);
        renderCreatePrompt();
        syncToggleButtons();
    }

    function sortKnobVersions(rows) {
        return [...rows].sort((a, b) => Number(b?.version ?? 0) - Number(a?.version ?? 0));
    }

    function getActiveKnob(rows) {
        return rows.find((item) => String(item?.status ?? '').toLowerCase() === 'active') || rows[0];
    }

    async function loadKnob() {
        el('knobLoading')?.classList.remove('d-none');
        el('knobError')?.classList.add('d-none');

        try {
            const params = new URLSearchParams({
                knob_type: el('knobTypeSelect')?.value || defaultKnobType
            });

            const response = await fetch(`${knobDataUrl}?${params.toString()}`, {
                method: 'GET',
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                let errorMessage = `API request failed with status ${response.status}`;
                let errorPayload = null;

                try {
                    errorPayload = await response.json();
                    if (typeof errorPayload?.message === 'string' && errorPayload.message.trim() !== '') {
                        errorMessage = errorPayload.message;
                    } else if (typeof errorPayload?.detail === 'string' && errorPayload.detail.trim() !== '') {
                        errorMessage = errorPayload.detail;
                    }
                } catch (_) {
                }

                if (response.status === 404 && errorPayload?.detail === 'knob_not_found') {
                    seedEmptyKnob();
                    return;
                }

                throw new Error(errorMessage);
            }

            const rows = await response.json();
            if (!Array.isArray(rows) || rows.length === 0) {
                throw new Error('No knob data returned from API.');
            }

            const sortedRows = sortKnobVersions(rows);
            const selectedKnob = getActiveKnob(sortedRows);
            const yamlText = selectedKnob?.knob || '';
            hasKnobData = true;

            el('knobLoading')?.classList.add('d-none');
            updateBadges(selectedKnob?.status || 'unknown', selectedKnob?.version);
            setYamlContent(yamlText);
            renderVersionModal(sortedRows);
            syncToggleButtons();
        } catch (error) {
            setError(error.message || 'Failed to load knob data.');
        }
    }

    function renderVersionModal(rows) {
        const versionCards = el('versionCards');
        if (!versionCards) return;

        versionCards.innerHTML = rows.map((item) => `
            <div class="border rounded p-3 bg-body">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <strong>v${item.version}</strong>
                        <span class="badge ${item.status === 'active' ? 'bg-success' : 'bg-secondary'}">${item.status}</span>
                    </div>
                    <div>
                        ${item.status === 'inactive'
                            ? '<button type="button" class="btn btn-sm btn-outline-success">Make Active</button>'
                            : '<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Active</button>'}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                    <span><i class="far fa-clock me-1"></i>${esc(item.updated_at)}</span>
                </div>
            </div>
        `).join('');
    }

    function enableEdit() {
        if (!hasKnobData) return;

        isEditMode = true;
        el('uiViewMode')?.classList.add('d-none');
        el('uiEditMode')?.classList.remove('d-none');
        el('yamlViewMode')?.classList.add('d-none');
        el('yamlEditMode')?.classList.remove('d-none');
        el('saveBtn')?.classList.remove('d-none');
        syncToggleButtons();

        initTextarea();
        onTextareaChange();
    }

    function startCreate() {
        seedEmptyKnob();
        hasKnobData = true;
        enableEdit();
    }

    function disableEdit() {
        isEditMode = false;
        el('uiViewMode')?.classList.remove('d-none');
        el('uiEditMode')?.classList.add('d-none');
        el('yamlViewMode')?.classList.remove('d-none');
        el('yamlEditMode')?.classList.add('d-none');
        el('saveBtn')?.classList.add('d-none');
        syncToggleButtons();
        el('invalidBadge')?.classList.add('d-none');
        el('footerError')?.classList.add('d-none');
        el('yamlEditor')?.classList.remove('is-valid', 'is-invalid');
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadKnob();

        el('knobForm')?.addEventListener('submit', (e) => {
            if (!isYamlValid) {
                e.preventDefault();
                el('footerError')?.classList.remove('d-none');
            }
        });
    });

    return { enableEdit, disableEdit, updateFromUi, addFieldToSection, startCreate };
})();
</script>
@endsection
