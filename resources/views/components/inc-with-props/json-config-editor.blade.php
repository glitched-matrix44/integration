@php
    $configData = $configData ?? '{}';
    $saveUrl = $saveUrl ?? '';
    $title = $title ?? 'Configuration Editor';
    $method = $method ?? 'POST';
    $configId = $configId ?? 'config_' . uniqid();
    $initialConfig = is_string($configData) ? $configData : json_encode($configData, JSON_PRETTY_PRINT);
@endphp

<div class="config-editor" id="{{ $configId }}">
    <div class="d-flex justify-content-between align-items-center py-2">
        <h5 class="mb-0 text-muted fs-6">
            <i class="fas fa-fw fa-cog me-2"></i>{{ $title }}
        </h5>
        <div>
            {{-- Show View Mode button initially, Edit Mode button hidden --}}
            <button type="button" class="btn btn-sm btn-outline-primary me-2 view-mode-btn d-none">
                <i class="fas fa-eye me-1"></i>View
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark edit-mode-btn">
                <i class="fas fa-edit me-1"></i>Edit
            </button>
        </div>
    </div>

    {{-- Success Alert --}}
    <div class="alert alert-success alert-dismissible fade d-none save-success-alert mb-3" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <span class="alert-message">Configuration saved successfully!</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    {{-- Error Alert --}}
    <div class="alert alert-danger alert-dismissible fade d-none save-error-alert mb-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <span class="alert-message"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    {{-- Mode Indicator --}}
    <div class="alert alert-info d-none edit-mode-indicator mb-3" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Edit Mode:</strong> Make your changes below and click "Save Changes" when done.
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#{{ $configId }}_tree" type="button">
                <i class="fas fa-sitemap me-1"></i>Tree View
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $configId }}_json" type="button">
                <i class="fas fa-code me-1"></i>JSON Editor
            </button>
        </li>
        {{-- Removed Raw JSON Tab --}}
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- Tree View Tab --}}
        <div class="tab-pane fade show active" id="{{ $configId }}_tree" role="tabpanel">
            <div class="tree-view-container"></div>
        </div>

        {{-- JSON Editor Tab --}}
        <div class="tab-pane fade" id="{{ $configId }}_json" role="tabpanel">
            <div class="mb-3">
                <div id="{{ $configId }}_jsoneditor" style="height: 400px;"></div>
                <small class="form-text text-muted">Direct JSON editing (advanced users)</small>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center my-3 border-top">
        <div>
            <button type="button" class="btn btn-outline-dark btn-sm reset-btn d-none">
                Reset Changes
            </button>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-btn d-none me-2">
                Cancel
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm save-btn d-none">
                Save Changes
            </button>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/jsoneditor@10.1.0/dist/jsoneditor.min.css" rel="stylesheet" type="text/css">
<style>
    /* Remove unnecessary space */
    #{{ $configId }} .config-editor {
        padding: 0;
    }
    
    #{{ $configId }} .tree-view-container {
        padding: 0;
    }
    
    #{{ $configId }} .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    #{{ $configId }} .mt-3 {
        margin-top: 1rem !important;
    }
    
    #{{ $configId }} .pt-3 {
        padding-top: 1rem !important;
    }
    
    /* Keep the original array display styling but reduce space */
    #{{ $configId }} .array-item {
        border-radius: 0.25rem;
        border-left: 3px solid #007bff;
        position: relative;
    }
    
    #{{ $configId }} .array-item-content {
        margin-left: 1rem;
    }
    
    /* Reduce form spacing */
    #{{ $configId }} .form-label {
        margin-bottom: 0.25rem;
    }
    
    #{{ $configId }} .border {
        border: 1px solid #dee2e6 !important;
    }
    
    #{{ $configId }} .rounded {
        border-radius: 0.375rem !important;
    }
    
    /* Array item styling */
    #{{ $configId }} .array-item-simple {
        border-radius: 0.25rem;
    }
    
    /* JSON Editor custom styling */
    #{{ $configId }}_jsoneditor .jsoneditor {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    
    #{{ $configId }}_jsoneditor .jsoneditor-menu {
        background-color: #0d6efd;
        border-bottom: 1px solid #0d6efd;
    }
    
    /* Fix for array item inputs */
    #{{ $configId }} .array-item input,
    #{{ $configId }} .array-item textarea {
        max-width: 100%;
    }
    
    #{{ $configId }} .array-item .form-control-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsoneditor@10.1.0/dist/jsoneditor.min.js"></script>
<script>
(function() {
    const container = document.getElementById('{{ $configId }}');
    if (!container) return;

    const treeContainer = container.querySelector('.tree-view-container');
    const jsonEditorContainer = document.getElementById('{{ $configId }}_jsoneditor');
    
    const editModeBtn = container.querySelector('.edit-mode-btn');
    const viewModeBtn = container.querySelector('.view-mode-btn');
    const saveBtn = container.querySelector('.save-btn');
    const resetBtn = container.querySelector('.reset-btn');
    const cancelBtn = container.querySelector('.cancel-edit-btn');
    
    const successAlert = container.querySelector('.save-success-alert');
    const errorAlert = container.querySelector('.save-error-alert');
    const editModeIndicator = container.querySelector('.edit-mode-indicator');
    
    // Parse the initial config properly
    let configData = @json(json_decode($initialConfig, false));
    let originalData = JSON.parse(JSON.stringify(configData));
    let isEditMode = false;
    let jsonEditor = null;
    let activeTab = 'tree';
    let treeViewChanged = false;
    let jsonEditorChanged = false;

    // Initialize
    init();

    function init() {
        initJsonEditor();
        renderTreeView();
        setupEventListeners();
        setupTabListeners();
    }

    function setupEventListeners() {
        editModeBtn.addEventListener('click', enableEditMode);
        viewModeBtn.addEventListener('click', disableEditMode);
        saveBtn.addEventListener('click', saveConfiguration);
        resetBtn.addEventListener('click', resetChanges);
        cancelBtn.addEventListener('click', disableEditMode);
    }

    function setupTabListeners() {
        // Listen to tab changes
        const tabButtons = container.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function(event) {
                const target = event.target.getAttribute('data-bs-target');
                if (target.includes('_tree')) {
                    activeTab = 'tree';
                    // If we were editing in JSON editor, sync changes to tree
                    if (jsonEditorChanged) {
                        try {
                            const jsonContent = jsonEditor.get();
                            configData = jsonContent;
                            renderTreeView();
                            treeViewChanged = true;
                            jsonEditorChanged = false;
                        } catch (e) {
                            console.error('Invalid JSON in editor:', e);
                        }
                    }
                } else if (target.includes('_json')) {
                    activeTab = 'json';
                    // If tree view was edited, update JSON editor
                    if (treeViewChanged) {
                        jsonEditor.set(configData);
                        treeViewChanged = false;
                        jsonEditorChanged = false;
                    }
                }
            });
        });
    }

    function initJsonEditor() {
        const options = {
            mode: 'view', // Start in view mode
            modes: ['tree', 'view', 'form', 'code', 'text'],
            onError: function(err) {
                console.error('JSONEditor error:', err);
            },
            onChange: function() {
                if (isEditMode) {
                    jsonEditorChanged = true;
                }
            }
        };
        
        jsonEditor = new JSONEditor(jsonEditorContainer, options, configData);
    }

    function enableEditMode() {
        isEditMode = true;
        editModeBtn.classList.add('d-none');
        viewModeBtn.classList.remove('d-none');
        saveBtn.classList.remove('d-none');
        resetBtn.classList.remove('d-none');
        cancelBtn.classList.remove('d-none');
        editModeIndicator.classList.remove('d-none');
        
        // Enable JSON editor
        jsonEditor.setMode('code');
        
        // Re-render tree view in edit mode
        renderTreeView();
    }

    function disableEditMode() {
        isEditMode = false;
        editModeBtn.classList.remove('d-none');
        viewModeBtn.classList.add('d-none');
        saveBtn.classList.add('d-none');
        resetBtn.classList.add('d-none');
        cancelBtn.classList.add('d-none');
        editModeIndicator.classList.add('d-none');
        
        // Disable JSON editor (set to view mode)
        jsonEditor.setMode('view');
        
        // Reset to original
        configData = JSON.parse(JSON.stringify(originalData));
        jsonEditor.set(configData);
        renderTreeView();
        treeViewChanged = false;
        jsonEditorChanged = false;
    }

    function resetChanges() {
        if (confirm('Are you sure you want to reset all changes?')) {
            configData = JSON.parse(JSON.stringify(originalData));
            jsonEditor.set(configData);
            renderTreeView();
            treeViewChanged = false;
            jsonEditorChanged = false;
        }
    }

    function renderTreeView() {
        treeContainer.innerHTML = '';
        const tree = createTreeNode(configData, '', []);
        treeContainer.appendChild(tree);
    }

    function createTreeNode(data, path, keys) {
        const container = document.createElement('div');
        container.className = 'p-1 mb-1';

        if (typeof data === 'object' && data !== null) {
            if (Array.isArray(data)) {
                const title = keys.length > 0 ? keys[keys.length - 1] : 'Array';
                
                container.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-list me-2"></i>
                            ${title}
                            <span class="badge bg-info-subtle text-success ms-2">${data.length} items</span>
                        </h6>
                        ${isEditMode ? `<button type="button" class="btn btn-sm btn-outline-primary add-array-item" data-path="${path}">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>` : ''}
                    </div>
                `;
                
                if (data.length === 0) {
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'text-muted fst-italic ms-3';
                    emptyMsg.textContent = '(empty array)';
                    container.appendChild(emptyMsg);
                } else {
                    data.forEach((item, index) => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'array-item border-start border-2 border-primary ms-2 mb-1 d-flex';
                        
                        if (isEditMode) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-sm btn-outline-danger btn-sm position-absolute top-0 end-0 mt-1 me-1';
                            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
                            removeBtn.addEventListener('click', () => removeArrayItem(path, index));
                            itemDiv.appendChild(removeBtn);
                        }
                        
                        const indexLabel = document.createElement('div');
                        // indexLabel.className = 'fw-semibold small mb-1';
                        // indexLabel.textContent = `Item ${index}`;
                        
                        itemDiv.appendChild(indexLabel);
                        
                        // Check if item is a simple value or object/array
                        if (typeof item === 'object' && item !== null) {
                            // If it's an object or array, create nested node
                            const content = createTreeNode(item, path + '[' + index + ']', [...keys, index]);
                            itemDiv.appendChild(content);
                        } else {
                            // Simple value - create editable input for array items
                            const valueContainer = document.createElement('div');
                            valueContainer.className = 'ms-2';
                            
                            if (isEditMode) {
                                // In edit mode, show input field
                                const valueDiv = document.createElement('div');
                                valueDiv.className = 'd-flex align-items-center gap-2';
                                
                                const input = createInputFieldForValue(item, path + '[' + index + ']');
                                input.className = 'form-control form-control-sm';
                                input.addEventListener('change', () => {
                                    const newValue = getTypedValue(input.value, typeof item);
                                    updateConfigValue(path + '[' + index + ']', newValue);
                                    treeViewChanged = true;
                                });
                                
                                valueDiv.appendChild(input);
                                valueContainer.appendChild(valueDiv);
                            } else {
                                // In view mode, show plain text
                                const display = document.createElement('div');
                                display.className = 'form-control-plaintext array-item-simple';
                                display.textContent = String(item);
                                valueContainer.appendChild(display);
                            }
                            
                            itemDiv.appendChild(valueContainer);
                        }
                        
                        container.appendChild(itemDiv);
                    });
                }

                if (isEditMode) {
                    const addBtn = container.querySelector('.add-array-item');
                    if (addBtn) {
                        addBtn.addEventListener('click', () => addArrayItem(path));
                    }
                }
            } else {
                // Object handling
                const title = keys.length > 0 ? keys[keys.length - 1] : 'Configuration';
                container.innerHTML = `
                    <div class="d-flex justify-content-start align-items-center mb-2 pb-1 gap-2 border-bottom">
                        <h6 class="mb-0 text-primary">
                            ${title}
                        </h6>
                        <span class="badge bg-info-subtle text-success">${Object.keys(data).length} properties</span>
                    </div>
                `;

                const row = document.createElement('div');
                row.className = 'row g-2';

                Object.entries(data).forEach(([key, value]) => {
                    const newPath = path ? `${path}.${key}` : key;
                    const newKeys = [...keys, key];
                    
                    // Handle arrays within objects differently
                    if (Array.isArray(value)) {
                        const col = document.createElement('div');
                        col.className = 'col-12';
                        col.appendChild(createTreeNode(value, newPath, newKeys));
                        row.appendChild(col);
                    } else if (typeof value === 'object' && value !== null) {
                        const col = document.createElement('div');
                        col.className = 'col-12';
                        col.appendChild(createTreeNode(value, newPath, newKeys));
                        row.appendChild(col);
                    } else {
                        const col = document.createElement('div');
                        col.className = 'col-md-6';
                        col.appendChild(createInputField(key, value, newPath));
                        row.appendChild(col);
                    }
                });

                container.appendChild(row);
            }
        } else {
            // Simple value at root level
            const keyName = keys.length > 0 ? keys[keys.length - 1] : 'Value';
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-2';
            
            const label = document.createElement('label');
            label.className = 'form-label fw-semibold text-capitalize';
            label.textContent = keyName.replace(/_/g, ' ');
            
            const valueEl = createSimpleValueDisplay(data, path);
            
            fieldDiv.appendChild(label);
            fieldDiv.appendChild(valueEl);
            container.appendChild(fieldDiv);
        }

        return container;
    }

    function createSimpleValueDisplay(value, path) {
        if (isEditMode) {
            return createInputFieldForValue(value, path);
        } else {
            const display = document.createElement('div');
            display.className = 'form-control-plaintext';
            
            if (value === null) {
                display.textContent = 'null';
                display.style.color = '#6c757d';
            } else if (typeof value === 'boolean') {
                display.textContent = value ? 'true' : 'false';
                display.style.color = value ? '#198754' : '#dc3545';
            } else if (typeof value === 'number') {
                display.textContent = value;
                display.style.color = '#0d6efd';
            } else {
                display.textContent = String(value);
            }
            
            return display;
        }
    }

    function getTypedValue(inputValue, originalType) {
        if (originalType === 'number') {
            return parseFloat(inputValue) || 0;
        } else if (originalType === 'boolean') {
            return inputValue.toLowerCase() === 'true';
        } else if (inputValue === 'null') {
            return null;
        } else {
            return inputValue;
        }
    }

    function createInputField(key, value, path) {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'mb-2';

        const label = document.createElement('label');
        label.className = 'form-label fw-semibold text-capitalize';
        label.textContent = key.replace(/_/g, ' ');

        const input = createInputFieldForValue(value, path);
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);

        const pathHint = document.createElement('small');
        pathHint.className = 'form-text text-muted';
        pathHint.textContent = path;
        fieldDiv.appendChild(pathHint);

        return fieldDiv;
    }

    function createInputFieldForValue(value, path) {
        const type = typeof value;
        let input;

        if (type === 'boolean') {
            const switchDiv = document.createElement('div');
            switchDiv.className = 'form-check form-switch';
            input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input';
            input.checked = value;
            input.disabled = !isEditMode;
            input.addEventListener('change', () => {
                updateConfigValue(path, input.checked);
                treeViewChanged = true;
            });
            
            const switchLabel = document.createElement('label');
            switchLabel.className = 'form-check-label';
            switchLabel.textContent = value ? 'Enabled' : 'Disabled';
            
            input.addEventListener('change', () => {
                switchLabel.textContent = input.checked ? 'Enabled' : 'Disabled';
            });
            
            switchDiv.appendChild(input);
            switchDiv.appendChild(switchLabel);
            return switchDiv;
        } else if (type === 'number') {
            input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control';
            input.value = value;
            input.step = value % 1 === 0 ? '1' : '0.01';
            input.disabled = !isEditMode;
            input.addEventListener('change', () => {
                updateConfigValue(path, parseFloat(input.value));
                treeViewChanged = true;
            });
            return input;
        } else if (value === null) {
            const select = document.createElement('select');
            select.className = 'form-control';
            select.disabled = !isEditMode;
            
            const options = [
                { value: 'null', text: 'null' },
                { value: 'true', text: 'true' },
                { value: 'false', text: 'false' },
                { value: 'text', text: 'text' },
                { value: 'number', text: 'number' }
            ];
            
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                select.appendChild(option);
            });
            
            select.value = 'null';
            
            const valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.className = 'form-control mt-1 d-none';
            valueInput.placeholder = 'Enter value';
            valueInput.disabled = !isEditMode;
            
            const container = document.createElement('div');
            container.appendChild(select);
            container.appendChild(valueInput);
            
            select.addEventListener('change', () => {
                if (select.value === 'text' || select.value === 'number') {
                    valueInput.classList.remove('d-none');
                } else {
                    valueInput.classList.add('d-none');
                }
            });
            
            valueInput.addEventListener('change', () => {
                let newValue;
                if (select.value === 'null') {
                    newValue = null;
                } else if (select.value === 'true') {
                    newValue = true;
                } else if (select.value === 'false') {
                    newValue = false;
                } else if (select.value === 'number') {
                    newValue = parseFloat(valueInput.value) || 0;
                } else {
                    newValue = valueInput.value;
                }
                updateConfigValue(path, newValue);
                treeViewChanged = true;
            });
            
            select.addEventListener('change', () => {
                let newValue;
                if (select.value === 'null') {
                    newValue = null;
                } else if (select.value === 'true') {
                    newValue = true;
                } else if (select.value === 'false') {
                    newValue = false;
                }
                if (newValue !== undefined) {
                    updateConfigValue(path, newValue);
                    treeViewChanged = true;
                }
            });
            
            return container;
        } else {
            const isLongText = String(value).length > 100;
            if (isLongText) {
                input = document.createElement('textarea');
                input.className = 'form-control font-monospace';
                input.rows = 2;
            } else {
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
            }
            input.value = value;
            input.disabled = !isEditMode;
            input.addEventListener('change', () => {
                updateConfigValue(path, input.value);
                treeViewChanged = true;
            });
            return input;
        }
    }

    function updateConfigValue(path, value) {
        const keys = path.replace(/\[/g, '.').replace(/\]/g, '').split('.').filter(k => k !== '');
        let current = configData;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            current = current[key];
        }
        
        const lastKey = keys[keys.length - 1];
        if (lastKey.match(/^\d+$/)) {
            // Array index
            current[parseInt(lastKey)] = value;
        } else {
            // Object property
            current[lastKey] = value;
        }
    }

    function addArrayItem(path) {
        const keys = path.replace(/\[/g, '.').replace(/\]/g, '').split('.').filter(k => k !== '');
        let current = configData;
        
        for (let i = 0; i < keys.length; i++) {
            current = current[keys[i]];
        }
        
        if (Array.isArray(current)) {
            // Determine what type of item to add based on existing items
            let newItem;
            if (current.length > 0) {
                // Clone the first item's structure
                if (typeof current[0] === 'object' && current[0] !== null) {
                    newItem = JSON.parse(JSON.stringify(current[0]));
                } else {
                    newItem = current[0];
                }
            } else {
                // Empty array - add an empty string
                newItem = '';
            }
            current.push(newItem);
            renderTreeView();
            treeViewChanged = true;
        }
    }

    function removeArrayItem(path, index) {
        if (!confirm('Are you sure you want to remove this item?')) return;
        
        const keys = path.replace(/\[/g, '.').replace(/\]/g, '').split('.').filter(k => k !== '');
        let current = configData;
        
        for (let i = 0; i < keys.length; i++) {
            current = current[keys[i]];
        }
        
        if (Array.isArray(current)) {
            current.splice(index, 1);
            renderTreeView();
            treeViewChanged = true;
        }
    }

    function saveConfiguration() {
        const saveUrl = '{{ $saveUrl }}';
        
        if (!saveUrl) {
            showError('Save URL not configured');
            return;
        }

        // Get the current data from active tab
        let dataToSave;
        if (activeTab === 'json' && jsonEditorChanged) {
            try {
                dataToSave = jsonEditor.get();
            } catch (e) {
                showError('Invalid JSON in editor: ' + e.message);
                return;
            }
        } else {
            dataToSave = configData;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        fetch(saveUrl, {
            method: '{{ $method }}',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(dataToSave)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message || 'Configuration saved successfully!');
                originalData = JSON.parse(JSON.stringify(dataToSave));
                configData = JSON.parse(JSON.stringify(dataToSave));
                jsonEditor.set(dataToSave);
                renderTreeView();
                treeViewChanged = false;
                jsonEditorChanged = false;
                disableEditMode();
            } else {
                showError(data.message || 'Failed to save configuration');
            }
        })
        .catch(error => {
            showError('Error saving configuration: ' + error.message);
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
        });
    }

    function showSuccess(message) {
        successAlert.querySelector('.alert-message').textContent = message;
        successAlert.classList.remove('d-none');
        successAlert.classList.add('show');
        setTimeout(() => {
            successAlert.classList.remove('show');
            setTimeout(() => successAlert.classList.add('d-none'), 150);
        }, 5000);
    }

    function showError(message) {
        errorAlert.querySelector('.alert-message').textContent = message;
        errorAlert.classList.remove('d-none');
        errorAlert.classList.add('show');
    }
})();
</script>
@endpush