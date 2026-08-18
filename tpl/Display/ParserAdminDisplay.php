<?php
$resolve = $this->_['resolve'];
$modularGridCssUrl = (string)$resolve('plugin/ClientStack/assets/modulargrid/styles/modulargrid.css');
$modularGridJsUrl = (string)$resolve('plugin/ClientStack/assets/modulargrid/index.js');
$translations = is_array($this->_['translations'] ?? null) ? $this->_['translations'] : [];
$modularGridStrings = $this->getBricks('clientstack_modulargrid');
$modularGridStrings = is_array($modularGridStrings) ? $modularGridStrings : [];
$service = (string)($this->_['service'] ?? '');
$t = static fn(string $key, string $fallback): string => trim((string)($translations[$key] ?? '')) !== ''
	? (string)$translations[$key]
	: $fallback;
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularGridCssUrl, ENT_QUOTES); ?>" />

<style>
	.parseflow-parser-shell {
		max-width: 1600px;
	}

	.parseflow-parser-shell h1 {
		margin: 0 0 8px 0;
		font-size: 24px;
		line-height: 1.2;
		font-weight: 600;
	}

	.parseflow-parser-shell > p {
		max-width: 1100px;
		margin: 0 0 14px 0;
		color: #555;
		line-height: 1.45;
	}

	.parseflow-parser-grid .parseflow-parser-panel {
		display: flex;
		align-items: center;
		flex-wrap: nowrap;
		gap: 8px;
		min-width: 0;
		width: 100%;
		padding: 8px 10px;
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		overflow-x: auto;
	}

	.parseflow-parser-grid .parseflow-parser-panel > * {
		flex: 0 0 auto;
	}

	.parseflow-parser-grid .parseflow-parser-main {
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		padding: 4px 0;
	}

	.parseflow-parser-grid .mg-table-scroll {
		height: 560px;
		overflow: auto;
		padding-bottom: 4px;
	}

	.parseflow-parser-grid .mg-table thead th {
		position: sticky;
		top: 0;
		z-index: 12;
		background: #fff;
	}

	.parseflow-parser-grid .mg-control-group {
		display: flex;
		flex-direction: row !important;
		align-items: center;
		flex-wrap: nowrap;
		gap: 6px;
		min-width: auto;
	}

	.parseflow-parser-grid .mg-control-group > * {
		flex: 0 0 auto;
	}

	.parseflow-parser-grid input[type="search"].mg-input {
		width: 280px;
	}

	.parseflow-parser-grid .mg-select {
		width: auto;
		min-width: 112px;
		max-width: 220px;
	}

	.parseflow-parser-grid .mg-label {
		white-space: nowrap;
		color: #666;
		font-size: 12px;
	}

	.parseflow-parser-grid .mg-input,
	.parseflow-parser-grid .mg-select,
	.parseflow-parser-grid .mg-button {
		min-height: 31px;
	}

	.parseflow-parser-cell {
		display: grid;
		gap: 2px;
		min-width: 0;
	}

	.parseflow-parser-name {
		font-weight: 600;
		color: #222;
		word-break: break-word;
	}

	.parseflow-parser-class {
		font-size: 11px;
		color: #777;
		word-break: break-word;
	}

	.parseflow-parser-pill-row {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 5px;
	}

	.parseflow-parser-pill {
		display: inline-flex;
		align-items: center;
		padding: 1px 7px;
		border: 1px solid #d6d6d6;
		border-radius: 999px;
		background: #fafafa;
		font-size: 11px;
		line-height: 1.4;
		white-space: nowrap;
	}

	.parseflow-parser-pill--enabled {
		background: #edf8ef;
		border-color: #b8d9bf;
		color: #2d6839;
	}

	.parseflow-parser-pill--disabled {
		background: #f5f5f5;
		border-color: #d9d9d9;
		color: #777;
	}

	.parseflow-parser-pill--error {
		background: #fff0f0;
		border-color: #e5b7b7;
		color: #8b2929;
	}

	.parseflow-parser-detail {
		display: grid;
		gap: 12px;
		padding: 12px 8px;
	}

	.parseflow-parser-detail-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		gap: 12px;
	}

	.parseflow-parser-detail-title {
		font-size: 15px;
		font-weight: 600;
		color: #222;
		word-break: break-word;
	}

	.parseflow-parser-detail-summary {
		margin-top: 2px;
		font-size: 12px;
		color: #666;
		word-break: break-word;
	}

	.parseflow-parser-detail-layout {
		display: grid;
		grid-template-columns: minmax(260px, .8fr) minmax(420px, 1.6fr);
		gap: 14px;
	}

	.parseflow-parser-section {
		border: 1px solid #e1e7ee;
		border-radius: 7px;
		background: #fff;
		padding: 12px;
		min-width: 0;
	}

	.parseflow-parser-section h3 {
		margin: 0 0 10px 0;
		font-size: 14px;
		font-weight: 600;
	}

	.parseflow-parser-facts {
		display: grid;
		grid-template-columns: max-content minmax(0, 1fr);
		gap: 5px 10px;
		margin: 0;
		font-size: 12px;
	}

	.parseflow-parser-facts dt {
		color: #666;
	}

	.parseflow-parser-facts dd {
		margin: 0;
		word-break: break-word;
	}

	.parseflow-parser-route-list {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
		margin-top: 10px;
	}

	.parseflow-parser-config-description {
		margin: 0 0 10px 0;
		font-size: 12px;
		line-height: 1.4;
		color: #666;
	}

	.parseflow-parser-config-fields {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		gap: 10px 12px;
	}

	.parseflow-parser-config-field {
		display: grid;
		gap: 4px;
		min-width: 0;
	}

	.parseflow-parser-config-label {
		font-size: 12px;
		font-weight: 600;
		color: #444;
	}

	.parseflow-parser-config-help {
		font-size: 11px;
		line-height: 1.35;
		color: #777;
	}

	.parseflow-parser-config-input,
	.parseflow-parser-config-select,
	.parseflow-parser-config-textarea {
		box-sizing: border-box;
		width: 100%;
		min-height: 31px;
		border: 1px solid #cfd7e1;
		border-radius: 4px;
		background: #fff;
		padding: 5px 7px;
		font: inherit;
		font-size: 12px;
	}

	.parseflow-parser-config-textarea {
		min-height: 80px;
		resize: vertical;
	}

	.parseflow-parser-checkbox {
		display: flex;
		align-items: center;
		gap: 7px;
		min-height: 31px;
	}

	.parseflow-parser-actions {
		display: flex;
		flex-wrap: wrap;
		gap: 7px;
		margin-top: 12px;
	}

	.parseflow-parser-button {
		min-height: 30px;
		border: 1px solid #bfc8d3;
		border-radius: 4px;
		background: #fff;
		padding: 4px 9px;
		font: inherit;
		font-size: 12px;
		cursor: pointer;
	}

	.parseflow-parser-button:hover {
		background: #f5f7fa;
	}

	.parseflow-parser-button:disabled {
		opacity: .6;
		cursor: default;
	}

	.parseflow-parser-error {
		margin-top: 8px;
		border: 1px solid #e5b7b7;
		border-radius: 4px;
		background: #fff4f4;
		padding: 7px 9px;
		font-size: 12px;
		color: #8b2929;
		word-break: break-word;
	}

	.parseflow-parser-output {
		min-height: 20px;
		margin-top: 8px;
		font-size: 12px;
		color: #555;
	}

	@media (max-width: 900px) {
		.parseflow-parser-detail-layout {
			grid-template-columns: 1fr;
		}
	}

	@media (max-width: 720px) {
		.parseflow-parser-grid input[type="search"].mg-input {
			width: 220px;
		}

		.parseflow-parser-grid .mg-table-scroll {
			height: 430px;
		}
	}
</style>

<div class="parseflow-parser-shell">
	<h1><?php echo htmlspecialchars($t('title', 'ParseFlow Parsers'), ENT_QUOTES); ?></h1>
	<p><?php echo htmlspecialchars($t('intro', 'Inspect discovered parser implementations. Parsers that implement ISchemaProvider expose their ParseFlow configuration here.'), ENT_QUOTES); ?></p>

	<div class="parseflow-parser-grid">
		<div id="parseflow-parser-grid"></div>
		<div id="parseflow-parser-output" class="parseflow-parser-output" aria-live="polite"></div>
	</div>
</div>

<script type="module">
	const modularGridModule = await import(new URL(<?php echo json_encode($modularGridJsUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>, document.baseURI).href);

	const {
		AjaxAdapter,
		ColumnVisibilityPlugin,
		FiltersPlugin,
		HeaderMenuPlugin,
		InfoPlugin,
		InfiniteScrollPlugin,
		ModularGrid,
		ResetPlugin,
		RowDetailPlugin,
		SearchPlugin,
		SessionStoragePlugin
	} = modularGridModule;

	const ENDPOINT_URL = <?php echo json_encode($service, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const GRID_SELECTOR = '#parseflow-parser-grid';
	const LOG_SELECTOR = '#parseflow-parser-output';
	const BATCH_SIZE = 40;
	const MODULAR_GRID_STRINGS = <?php echo json_encode($modularGridStrings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	const LABELS = <?php echo json_encode([
		'yes' => $t('yes_label', 'Yes'),
		'no' => $t('no_label', 'No'),
		'enabled' => $t('enabled', 'Enabled'),
		'disabled' => $t('disabled', 'Disabled'),
		'no_status' => $t('no_status', 'n/a'),
		'details' => $t('details', 'Parser details'),
		'technical_name' => $t('technical_name', 'Technical name'),
		'class' => $t('class', 'Class'),
		'settings' => $t('settings', 'Settings'),
		'configured' => $t('configured', 'Configured'),
		'routes' => $t('routes', 'Provided routes'),
		'configuration' => $t('configuration', 'Configuration'),
		'no_configuration' => $t('no_configuration', 'This parser does not expose a configuration schema.'),
		'save' => $t('save', 'Save'),
		'reset' => $t('reset', 'Reset to defaults'),
		'saved' => $t('saved', 'Parser configuration saved for {parser}.'),
		'reset_done' => $t('reset_done', 'Parser configuration reset for {parser}.'),
		'request_failed' => $t('request_failed', 'Parser configuration request failed.'),
		'save_failed' => $t('save_failed', 'Failed to save parser configuration.'),
		'reset_failed' => $t('reset_failed', 'Failed to reset parser configuration.'),
		'error' => $t('error', 'Error'),
		'missing_parser_row' => $t('missing_parser_row', 'Missing parser row.'),
		'parser_detail_not_found' => $t('parser_detail_not_found', 'Parser detail not found.'),
		'parser_fallback' => $t('parser_fallback', 'Parser'),
		'loading_parser_details' => $t('loading_parser_details', 'Loading parser details...'),
		'loaded_more_parsers' => $t('loaded_more_parsers', 'Loaded {appended} more parsers. {total} rows are currently loaded.'),
		'loaded_parser_detail' => $t('loaded_parser_detail', 'Loaded parser detail for {parser}.'),
		'failed_parser_detail' => $t('failed_parser_detail', 'Failed to load parser detail.'),
		'initialized' => $t('initialized', 'ParseFlow parser administration initialized.'),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

	const layout = {
		type: 'stack',
		className: 'mg-layout-root',
		children: [
			{
				type: 'zone',
				key: 'topLine1',
				className: 'parseflow-parser-panel'
			},
			{
				type: 'view',
				key: 'main',
				className: 'parseflow-parser-main'
			},
			{
				type: 'zone',
				key: 'statusZone',
				className: 'parseflow-parser-panel'
			}
		]
	};

	let grid = null;

	function formatLabel(template, replacements = {}) {
		return Object.entries(replacements).reduce((value, [key, replacement]) => {
			return value.replaceAll('{' + key + '}', String(replacement));
		}, String(template || ''));
	}

	function text(value, fallback = '') {
		if (value === null || value === undefined) return fallback;
		const normalized = String(value).trim();
		return normalized === '' ? fallback : normalized;
	}

	function el(tag, className = '', content = '') {
		const element = document.createElement(tag);
		if (className) element.className = className;
		if (content !== '') element.textContent = content;
		return element;
	}

	function pill(content, modifier = '') {
		return el('span', 'parseflow-parser-pill' + (modifier ? ' parseflow-parser-pill--' + modifier : ''), content);
	}

	function setLog(message) {
		const log = document.querySelector(LOG_SELECTOR);
		if (log) log.textContent = text(message);
	}

	function resolveSortForRequest(request) {
		return {
			key: request.sortKey || 'name',
			dir: request.sortDirection || 'asc',
			type: request.sortKey === 'routeCount' ? 'int' : 'string'
		};
	}

	async function postJson(payload) {
		const response = await fetch(ENDPOINT_URL, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		});

		if (!response.ok) {
			throw new Error(LABELS.request_failed + ' (' + String(response.status) + ')');
		}

		const data = await response.json();
		if (!data || data.success !== true) {
			if (data && data.error) console.error(data.error);
			throw new Error(LABELS.request_failed);
		}

		return data;
	}

	async function loadRemoteDetail(context) {
		const row = context && context.row ? context.row : null;
		if (!row) throw new Error(LABELS.missing_parser_row);

		const response = await postJson({
			mode: 'detail',
			id: row.id || row.name || '',
			parser: row.name || row.id || ''
		});

		if (!response.found || !response.detail) {
			throw new Error(LABELS.parser_detail_not_found);
		}

		return response.detail;
	}

	function schemaProperties(schema) {
		return schema && schema.properties && typeof schema.properties === 'object' && !Array.isArray(schema.properties)
			? schema.properties
			: {};
	}

	function createControl(key, definition, value) {
		const type = text(definition && definition.type, 'string').toLowerCase();
		const enumValues = definition && Array.isArray(definition.enum) ? definition.enum : [];
		let control;
		let wrapper;

		if (type === 'boolean') {
			wrapper = el('div', 'parseflow-parser-checkbox');
			control = document.createElement('input');
			control.type = 'checkbox';
			control.checked = !!value;
			const state = el('span', 'parseflow-parser-config-help', control.checked ? LABELS.enabled : LABELS.disabled);
			wrapper.appendChild(control);
			wrapper.appendChild(state);
			control.addEventListener('change', () => {
				state.textContent = control.checked ? LABELS.enabled : LABELS.disabled;
			});
		}
		else if (type === 'array') {
			const itemDefinition = definition && definition.items && typeof definition.items === 'object' ? definition.items : {};
			const itemEnum = Array.isArray(itemDefinition.enum) ? itemDefinition.enum : [];
			if (itemEnum.length > 0) {
				control = document.createElement('select');
				control.multiple = true;
				control.className = 'parseflow-parser-config-select';
				const selected = new Set(Array.isArray(value) ? value.map(String) : []);
				itemEnum.forEach((item) => {
					const option = document.createElement('option');
					option.value = String(item);
					option.textContent = String(item);
					option.selected = selected.has(String(item));
					control.appendChild(option);
				});
			}
			else {
				control = document.createElement('textarea');
				control.className = 'parseflow-parser-config-textarea';
				control.value = Array.isArray(value) ? value.join('\n') : '';
			}
			control.dataset.itemType = text(itemDefinition.type, 'string').toLowerCase();
			wrapper = control;
		}
		else if (type === 'object') {
			control = document.createElement('textarea');
			control.className = 'parseflow-parser-config-textarea';
			control.value = value && typeof value === 'object' ? JSON.stringify(value, null, 2) : '{}';
			wrapper = control;
		}
		else if (enumValues.length > 0) {
			control = document.createElement('select');
			control.className = 'parseflow-parser-config-select';
			enumValues.forEach((item) => {
				const option = document.createElement('option');
				option.value = String(item);
				option.textContent = String(item);
				control.appendChild(option);
			});
			control.value = value === null || value === undefined ? '' : String(value);
			wrapper = control;
		}
		else {
			control = document.createElement('input');
			control.className = 'parseflow-parser-config-input';
			control.type = type === 'integer' || type === 'number' ? 'number' : 'text';
			if (definition && definition.minimum !== undefined) control.min = String(definition.minimum);
			if (definition && definition.maximum !== undefined) control.max = String(definition.maximum);
			if (type === 'integer') control.step = '1';
			if (type === 'number') control.step = 'any';
			control.value = value === null || value === undefined ? '' : String(value);
			wrapper = control;
		}

		control.dataset.configKey = key;
		control.dataset.schemaType = type;
		return { control, wrapper };
	}

	function readControl(control) {
		const type = text(control.dataset.schemaType, 'string').toLowerCase();
		if (type === 'boolean') return !!control.checked;
		if (type === 'integer') return Number.parseInt(control.value || '0', 10);
		if (type === 'number') return Number(control.value || '0');
		if (type === 'array') {
			if (control instanceof HTMLSelectElement) {
				return Array.from(control.selectedOptions).map((option) => option.value);
			}
			const values = String(control.value || '').split(/[\r\n,]+/).map((item) => item.trim()).filter(Boolean);
			const itemType = text(control.dataset.itemType, 'string').toLowerCase();
			if (itemType === 'integer') return values.map((item) => Number.parseInt(item, 10));
			if (itemType === 'number') return values.map((item) => Number(item));
			return values;
		}
		if (type === 'object') {
			const value = String(control.value || '').trim();
			return value === '' ? {} : JSON.parse(value);
		}
		return String(control.value || '');
	}

	function updateGridRowPresentation(row) {
		if (!row || !row.name) return;

		document.querySelectorAll('[data-parseflow-parser-name]').forEach((element) => {
			if (element.dataset.parseflowParserName !== row.name) return;
			const field = element.dataset.parseflowParserField || '';

			if (field === 'routes') {
				element.textContent = String(row.routeCount || 0);
			}
			else if (field === 'status') {
				element.className = 'parseflow-parser-pill';
				if (row.status === 'error') {
					element.textContent = LABELS.error;
					element.classList.add('parseflow-parser-pill--error');
				}
				else if (row.enabled === true) {
					element.textContent = LABELS.enabled;
					element.classList.add('parseflow-parser-pill--enabled');
				}
				else if (row.enabled === false) {
					element.textContent = LABELS.disabled;
					element.classList.add('parseflow-parser-pill--disabled');
				}
				else {
					element.textContent = LABELS.no_status;
				}
			}
		});
	}

	function createDetailsSection(detail) {
		const section = el('div', 'parseflow-parser-section');
		section.appendChild(el('h3', '', LABELS.details));
		const facts = document.createElement('dl');
		facts.className = 'parseflow-parser-facts';

		[
			[LABELS.technical_name, detail.name],
			[LABELS.class, detail.class],
			[LABELS.routes, String(detail.routeCount || 0)],
			[LABELS.settings, detail.configurable ? detail.settingsGroup + '/' + detail.name : '-'],
			[LABELS.configured, detail.configured ? LABELS.yes : LABELS.no]
		].forEach(([key, value]) => {
			facts.appendChild(el('dt', '', key));
			facts.appendChild(el('dd', '', value));
		});
		section.appendChild(facts);

		if (detail.routeError) section.appendChild(el('div', 'parseflow-parser-error', detail.routeError));
		if (Array.isArray(detail.routes) && detail.routes.length > 0) {
			const routeList = el('div', 'parseflow-parser-route-list');
			detail.routes.forEach((route) => routeList.appendChild(pill(route)));
			section.appendChild(routeList);
		}

		return section;
	}

	function createConfigSection(detail, context, detailRoot) {
		const section = el('div', 'parseflow-parser-section');
		section.appendChild(el('h3', '', LABELS.configuration));

		if (detail.schemaError) {
			section.appendChild(el('div', 'parseflow-parser-error', detail.schemaError));
			return section;
		}
		if (!detail.configurable) {
			section.appendChild(el('div', 'parseflow-parser-config-description', LABELS.no_configuration));
			return section;
		}

		const schema = detail.schema || {};
		const config = detail.effectiveConfig || {};
		const required = new Set(Array.isArray(schema.required) ? schema.required.map(String) : []);
		const controls = new Map();

		if (schema.description) {
			section.appendChild(el('div', 'parseflow-parser-config-description', schema.description));
		}

		const fields = el('div', 'parseflow-parser-config-fields');
		Object.entries(schemaProperties(schema)).forEach(([key, definition]) => {
			if (!definition || typeof definition !== 'object') return;
			const field = el('label', 'parseflow-parser-config-field');
			const title = text(definition.title || definition.label, key) + (required.has(key) ? ' *' : '');
			field.appendChild(el('div', 'parseflow-parser-config-label', title));
			const rendered = createControl(key, definition, config[key]);
			field.appendChild(rendered.wrapper);
			if (definition.description) field.appendChild(el('div', 'parseflow-parser-config-help', definition.description));
			controls.set(key, rendered.control);
			fields.appendChild(field);
		});
		section.appendChild(fields);

		const actions = el('div', 'parseflow-parser-actions');
		const save = el('button', 'parseflow-parser-button', LABELS.save);
		save.type = 'button';
		save.addEventListener('click', async (event) => {
			event.preventDefault();
			event.stopPropagation();
			save.disabled = true;
			try {
				const configPayload = {};
				controls.forEach((control, key) => {
					configPayload[key] = readControl(control);
				});
				const response = await postJson({ mode: 'save', parser: detail.name, config: configPayload });
				if (response.row && context && context.row) Object.assign(context.row, response.row);
				updateGridRowPresentation(response.row || {});
				renderDetailInto(detailRoot, response.detail || detail, context);
				setLog(formatLabel(LABELS.saved, { parser: detail.name }));
			}
			catch (error) {
				console.error(error);
				setLog(LABELS.save_failed);
				save.disabled = false;
			}
		});
		actions.appendChild(save);

		const reset = el('button', 'parseflow-parser-button', LABELS.reset);
		reset.type = 'button';
		reset.addEventListener('click', async (event) => {
			event.preventDefault();
			event.stopPropagation();
			reset.disabled = true;
			try {
				const response = await postJson({ mode: 'reset', parser: detail.name });
				if (response.row && context && context.row) Object.assign(context.row, response.row);
				updateGridRowPresentation(response.row || {});
				renderDetailInto(detailRoot, response.detail || detail, context);
				setLog(formatLabel(LABELS.reset_done, { parser: detail.name }));
			}
			catch (error) {
				console.error(error);
				setLog(LABELS.reset_failed);
				reset.disabled = false;
			}
		});
		actions.appendChild(reset);
		section.appendChild(actions);

		return section;
	}

	function renderDetailInto(root, detail, context) {
		root.replaceChildren();

		const header = el('div', 'parseflow-parser-detail-header');
		const heading = el('div', '');
		heading.appendChild(el('div', 'parseflow-parser-detail-title', text(detail.name, LABELS.parser_fallback)));
		heading.appendChild(el('div', 'parseflow-parser-detail-summary', text(detail.class)));
		header.appendChild(heading);
		root.appendChild(header);

		const badges = el('div', 'parseflow-parser-pill-row');
		badges.appendChild(pill(String(detail.routeCount || 0) + ' ' + LABELS.routes));
		badges.appendChild(pill(detail.configurable ? LABELS.configuration : LABELS.no_status));
		if (detail.enabled === true) badges.appendChild(pill(LABELS.enabled, 'enabled'));
		if (detail.enabled === false) badges.appendChild(pill(LABELS.disabled, 'disabled'));
		root.appendChild(badges);

		const detailLayout = el('div', 'parseflow-parser-detail-layout');
		detailLayout.appendChild(createDetailsSection(detail));
		detailLayout.appendChild(createConfigSection(detail, context, root));
		root.appendChild(detailLayout);
	}

	function renderParserDetail(context) {
		const detail = context && context.payload ? context.payload : {};
		const root = el('div', 'parseflow-parser-detail');
		renderDetailInto(root, detail, context);
		return root;
	}

	function renderParser(value, row) {
		const cell = el('div', 'parseflow-parser-cell');
		cell.appendChild(el('div', 'parseflow-parser-name', text(value, text(row && row.name))));
		cell.appendChild(el('div', 'parseflow-parser-class', text(row && row.class)));
		return cell;
	}

	function renderRoutes(value, row) {
		const element = pill(String(value || 0));
		element.dataset.parseflowParserName = text(row && row.name);
		element.dataset.parseflowParserField = 'routes';
		return element;
	}

	function renderConfiguration(value) {
		return pill(value ? LABELS.yes : LABELS.no);
	}

	function renderStatus(value, row) {
		let element;
		if (value === 'error') element = pill(LABELS.error, 'error');
		else if (row && row.enabled === true) element = pill(LABELS.enabled, 'enabled');
		else if (row && row.enabled === false) element = pill(LABELS.disabled, 'disabled');
		else element = pill(LABELS.no_status);
		element.dataset.parseflowParserName = text(row && row.name);
		element.dataset.parseflowParserField = 'status';
		return element;
	}

	function createLoadingDetail() {
		return el('div', 'parseflow-parser-detail-summary', LABELS.loading_parser_details);
	}

	function createErrorDetail(context) {
		return el('div', 'parseflow-parser-error', text(context && context.error, LABELS.request_failed));
	}

	(async function() {
		const root = document.querySelector(GRID_SELECTOR);
		if (!root || root.dataset.initialized === '1') return;
		root.dataset.initialized = '1';

		const adapter = new AjaxAdapter({
			url: ENDPOINT_URL,
			method: 'POST',
			rowsPath: 'data',
			totalPath: 'total',
			mapRequest(request) {
				const state = grid ? grid.getState() : {};
				return {
					mode: 'page',
					page: request.page || 1,
					pageSize: request.pageSize || BATCH_SIZE,
					search: request.search || state.query?.search || '',
					sort: [resolveSortForRequest(request)],
					filters: state.filters || {}
				};
			}
		});

		grid = new ModularGrid(GRID_SELECTOR, {
			strings: MODULAR_GRID_STRINGS,
			layout,
			adapter,
			dataMode: 'server',
			server: {
				searchDebounceMs: 350,
				watchStateKeys: ['query', 'filters']
			},
			features: {
				paging: false
			},
			pageSize: BATCH_SIZE,
			sort: {
				key: 'name',
				direction: 'asc'
			},
			plugins: [
				SearchPlugin,
				FiltersPlugin,
				HeaderMenuPlugin,
				InfoPlugin,
				ColumnVisibilityPlugin,
				ResetPlugin,
				SessionStoragePlugin,
				RowDetailPlugin,
				InfiniteScrollPlugin
			],
			pluginOptions: {
				search: {
					zone: 'topLine1',
					order: 10,
					label: MODULAR_GRID_STRINGS.search || 'Search',
					placeholder: <?php echo json_encode($t('search_placeholder', 'Filter parser or class'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
				},
				filters: {
					zone: 'topLine1',
					order: 20,
					stateKey: 'filters',
					showClearButton: false,
					fields: [
						{
							key: 'configuration',
							defaultValue: '',
							label: <?php echo json_encode($t('column_configuration', 'Configuration'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
							type: 'select',
							options: [
								{ value: '', label: '' },
								{ value: 'yes', label: LABELS.yes },
								{ value: 'no', label: LABELS.no }
							]
						},
						{
							key: 'status',
							defaultValue: '',
							label: <?php echo json_encode($t('column_status', 'Status'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
							type: 'select',
							options: [
								{ value: '', label: '' },
								{ value: 'yes', label: LABELS.yes },
								{ value: 'no', label: LABELS.no },
								{ value: 'na', label: LABELS.no_status }
							]
						}
					]
				},
				headerMenu: {
					showSortActions: true,
					showClearSortAction: true,
					showHideColumnAction: true
				},
				columnVisibility: {
					zone: ''
				},
				reset: {
					zone: 'topLine1',
					order: 30,
					label: MODULAR_GRID_STRINGS.reset || 'Reset',
					sections: ['query', 'filters', 'columns']
				},
				sessionStorage: {
					key: 'parseflow-parser-admin-grid-v3',
					sections: ['query', 'filters', 'columns']
				},
				info: {
					zone: 'statusZone',
					order: 10,
					displayMode: 'loaded'
				},
				rowDetail: {
					rowIdKey: 'id',
					clearOnDataReload: true,
					asyncDetail: {
						load(context) {
							return loadRemoteDetail(context);
						},
						renderLoading() {
							return createLoadingDetail();
						},
						renderError(context) {
							return createErrorDetail(context);
						},
						render(context) {
							return renderParserDetail(context);
						}
					}
				},
				infiniteScroll: {
					threshold: 180,
					pageSize: BATCH_SIZE,
					containerSelector: '.mg-table-scroll'
				}
			},
			columns: [
				{
					key: 'name',
					label: <?php echo json_encode($t('column_parser', 'Parser'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
					width: 500,
					headerMenu: {
						defaultSortKey: 'name',
						defaultSortDirection: 'asc',
						sortOptions: [
							{ key: 'name', label: <?php echo json_encode($t('column_parser', 'Parser'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> },
							{ key: 'class', label: <?php echo json_encode($t('class', 'Class'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> },
							{ key: 'routeCount', label: <?php echo json_encode($t('column_routes', 'Routes'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> }
						]
					},
					render(value, row) {
						return renderParser(value, row);
					}
				},
				{
					key: 'routeCount',
					label: <?php echo json_encode($t('column_routes', 'Routes'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
					width: 110,
					render(value, row) {
						return renderRoutes(value, row);
					}
				},
				{
					key: 'configurable',
					label: <?php echo json_encode($t('column_configuration', 'Configuration'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
					width: 150,
					render(value) {
						return renderConfiguration(value);
					}
				},
				{
					key: 'status',
					label: <?php echo json_encode($t('column_status', 'Status'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
					width: 140,
					render(value, row) {
						return renderStatus(value, row);
					}
				},
				{
					key: 'class',
					label: <?php echo json_encode($t('class', 'Class'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
					width: 520,
					visible: false
				}
			]
		});

		grid.on('data:appended', ({ appendedCount, totalLoaded }) => {
			setLog(formatLabel(LABELS.loaded_more_parsers, { appended: appendedCount, total: totalLoaded }));
		});

		grid.on('detail:loaded', (event) => {
			const row = event && typeof event === 'object' ? event.row : null;
			setLog(formatLabel(LABELS.loaded_parser_detail, { parser: text(row && row.name) }));
		});
		grid.on('detail:error', (event) => {
			if (event && event.error) console.error(event.error);
			setLog(LABELS.failed_parser_detail);
		});

		await grid.init();
		setLog(LABELS.initialized);
	})();
</script>
