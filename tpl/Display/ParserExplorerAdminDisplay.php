<?php
$resolve = $this->_['resolve'];

$modularGridCssUrl = (string) $resolve('plugin/ClientStack/assets/modulargrid/styles/modulargrid.css');
$modularGridJsUrl = (string) $resolve('plugin/ClientStack/assets/modulargrid/index.js');
$summary = is_array($this->_['summary'] ?? null) ? $this->_['summary'] : [];
$options = is_array($this->_['options'] ?? null) ? $this->_['options'] : [];
$summaryCards = [
	['Parsers', (int) ($summary['parserCount'] ?? 0)],
	['Routes', (int) ($summary['routeCount'] ?? 0)],
	['States', (int) ($summary['stateCount'] ?? 0)],
	['Outputs', (int) ($summary['outputCount'] ?? 0)],
	['External', (int) ($summary['externalRouteCount'] ?? 0)],
	['Lossy', (int) ($summary['lossyRouteCount'] ?? 0)],
];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularGridCssUrl, ENT_QUOTES); ?>" />

<style>
	.parser-explorer-shell {
		max-width: 1700px;
	}

	.parser-explorer-shell h1 {
		margin: 0 0 8px 0;
		font-size: 24px;
		line-height: 1.2;
		font-weight: 600;
	}

	.parser-explorer-shell p {
		margin: 0 0 14px 0;
		max-width: 1200px;
		color: #555;
		line-height: 1.45;
	}

	.parser-explorer-summary {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
		gap: 10px;
		margin: 0 0 12px 0;
	}

	.parser-explorer-card {
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		padding: 10px 12px;
		min-width: 0;
	}

	.parser-explorer-card-label {
		font-size: 12px;
		color: #666;
		margin-bottom: 4px;
	}

	.parser-explorer-card-value {
		font-size: 22px;
		line-height: 1.2;
		font-weight: 600;
		color: #222;
	}

	.parser-explorer-grid .parser-explorer-panel {
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

	.parser-explorer-grid .parser-explorer-panel--filters {
		flex-wrap: wrap;
		align-items: flex-start;
		overflow-x: visible;
	}

	.parser-explorer-grid .parser-explorer-panel--main {
		align-items: center;
	}

	.parser-explorer-grid .parser-explorer-panel--filters .mg-control-group {
		flex-wrap: nowrap;
		align-items: center;
	}

	.parser-explorer-grid .parser-explorer-panel > * {
		flex: 0 0 auto;
	}

	.parser-explorer-grid .parser-explorer-main {
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		padding: 4px 0;
	}

	.parser-explorer-grid .mg-control-group {
		flex-direction: row;
		align-items: center;
		gap: 6px;
		min-width: auto;
	}

	.parser-explorer-grid .mg-label {
		white-space: nowrap;
		color: #666;
		font-size: 12px;
	}

	.parser-explorer-grid .mg-input,
	.parser-explorer-grid .mg-select,
	.parser-explorer-grid .mg-button {
		min-height: 28px;
		font-size: 13px;
	}

	.parser-explorer-grid input[type="search"].mg-input {
		width: 280px;
	}

	.parser-explorer-grid .mg-select {
		width: auto;
		min-width: 112px;
		max-width: 230px;
	}

	.parser-explorer-grid .parser-explorer-filter-input .mg-select {
		min-width: 170px;
		max-width: 260px;
	}

	.parser-explorer-grid .parser-explorer-filter-output .mg-select {
		min-width: 170px;
		max-width: 260px;
	}

	.parser-explorer-grid .mg-table-scroll {
		height: 560px;
		overflow: auto;
		padding-bottom: 4px;
	}

	.parser-explorer-grid .mg-table thead th {
		position: sticky;
		top: 0;
		z-index: 12;
		background: #fff;
	}

	.parser-explorer-grid .mg-table th,
	.parser-explorer-grid .mg-table td {
		padding: 6px 8px;
		font-size: 13px;
		vertical-align: top;
	}

	.parser-explorer-cell-stack {
		display: grid;
		gap: 2px;
		min-width: 0;
	}

	.parser-explorer-cell-main {
		font-weight: 600;
		color: #222;
		word-break: break-word;
	}

	.parser-explorer-cell-sub {
		font-size: 12px;
		color: #666;
		word-break: break-word;
	}

	.parser-explorer-pill-row {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		align-items: center;
	}

	.parser-explorer-pill {
		display: inline-flex;
		align-items: center;
		padding: 1px 6px;
		border: 1px solid #d6d6d6;
		border-radius: 999px;
		background: #fafafa;
		font-size: 11px;
		line-height: 1.35;
		color: #444;
		white-space: nowrap;
	}

	.parser-explorer-pill-strong {
		background: #f0f0f0;
		color: #222;
		border-color: #cfcfcf;
	}

	.parser-explorer-pill-warning {
		background: #fff8e5;
		border-color: #ead08a;
		color: #785c08;
	}

	.parser-explorer-output {
		margin-top: 12px;
		padding: 8px 0 0 0;
		border-top: 1px solid #e2e2e2;
		font-size: 13px;
		color: #555;
	}

	.parser-explorer-output strong {
		color: #222;
	}

	.parser-explorer-detail {
		display: grid;
		gap: 12px;
		min-width: 0;
	}

	.parser-explorer-detail-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
		min-width: 0;
	}

	.parser-explorer-detail-title {
		font-size: 15px;
		font-weight: 600;
		color: #1f2c3c;
	}

	.parser-explorer-detail-summary {
		font-size: 12px;
		color: #5f6d7e;
	}

	.parser-explorer-detail-actions {
		display: inline-flex;
		align-items: flex-start;
		justify-content: flex-end;
		gap: 6px;
		flex: 0 0 auto;
	}

	.parser-explorer-button {
		appearance: none;
		border: 1px solid #cfcfcf;
		border-radius: 4px;
		background: #fff;
		color: #222;
		cursor: pointer;
		font: inherit;
		font-size: 12px;
		line-height: 1.3;
		padding: 4px 8px;
		white-space: nowrap;
	}

	.parser-explorer-button:hover {
		background: #f5f5f5;
	}

	.parser-explorer-detail-layout {
		display: grid;
		grid-template-columns: minmax(300px, 440px) minmax(360px, 1fr);
		align-items: start;
		gap: 16px;
		min-width: 0;
	}

	.parser-explorer-detail-left,
	.parser-explorer-detail-right {
		display: grid;
		gap: 12px;
		min-width: 0;
	}

	.parser-explorer-section {
		border: 1px solid #e2e2e2;
		border-radius: 6px;
		background: #fff;
		padding: 10px 12px;
		min-width: 0;
	}

	.parser-explorer-section-title {
		margin: 0 0 8px 0;
		font-size: 12px;
		font-weight: 600;
		letter-spacing: 0.03em;
		text-transform: uppercase;
		color: #546274;
	}

	.parser-explorer-field-list {
		display: grid;
		gap: 6px;
	}

	.parser-explorer-field {
		display: grid;
		grid-template-columns: 120px minmax(0, 1fr);
		gap: 8px;
		font-size: 12px;
	}

	.parser-explorer-field-label {
		color: #667587;
	}

	.parser-explorer-field-value {
		color: #222;
		word-break: break-word;
	}

	.parser-explorer-step-list {
		display: grid;
		gap: 6px;
	}

	.parser-explorer-step {
		border: 1px solid #e7e7e7;
		border-radius: 5px;
		background: #fafafa;
		padding: 8px 10px;
		font-size: 12px;
	}

	.parser-explorer-code {
		margin: 0;
		padding: 10px 12px;
		border: 1px solid #dce4ed;
		border-radius: 6px;
		background: #f8fafc;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		font-size: 12px;
		line-height: 1.45;
		white-space: pre;
		overflow: auto;
		max-height: 520px;
	}

	.parser-explorer-optional-filter-picker {
		order: 40;
	}

	.parser-explorer-optional-filter-picker .mg-select {
		min-width: 150px;
	}

	.parser-explorer-optional-filter-remove {
		appearance: none;
		border: 1px solid #d4d4d4;
		border-radius: 999px;
		background: #fff;
		color: #555;
		cursor: pointer;
		font: inherit;
		font-size: 11px;
		line-height: 1;
		min-height: 22px;
		min-width: 22px;
		padding: 0 6px;
	}

	.parser-explorer-optional-filter-remove:hover {
		background: #f5f5f5;
		color: #222;
	}

	@media (max-width: 980px) {
		.parser-explorer-detail-layout {
			grid-template-columns: minmax(0, 1fr);
		}
	}

	@media (max-width: 720px) {
		.parser-explorer-shell h1 {
			font-size: 21px;
		}

		.parser-explorer-grid input[type="search"].mg-input {
			width: 220px;
		}

		.parser-explorer-grid .mg-table-scroll {
			height: 430px;
		}
	}
</style>

<div class="parser-explorer-shell">
	<h1>ParseFlow Explorer</h1>
	<p>
		Select an input and output representation to explore the best available parser combinations for exactly that conversion. Open a row to see the route chain and PHP code for calling it through <code>IParserService</code>.
	</p>

	<div class="parser-explorer-summary">
		<?php foreach($summaryCards as $card): ?>
			<div class="parser-explorer-card">
				<div class="parser-explorer-card-label"><?php echo htmlspecialchars((string) $card[0], ENT_QUOTES); ?></div>
				<div class="parser-explorer-card-value"><?php echo htmlspecialchars((string) $card[1], ENT_QUOTES); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="parser-explorer-grid">
		<div id="parser-explorer-grid"></div>
		<div id="parser-explorer-output" class="parser-explorer-output"></div>
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
		ModularGrid,
		ResetPlugin,
		RowActionsPlugin,
		RowDetailPlugin,
		SearchPlugin,
		SessionStoragePlugin
	} = modularGridModule;

	const ENDPOINT_URL = <?php echo json_encode((string) $this->_['service'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const GRID_SELECTOR = '#parser-explorer-grid';
	const LOG_SELECTOR = '#parser-explorer-output';
	const OPTIONS = <?php echo json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const BATCH_SIZE = 40;
	const SORT_TYPES = {
		totalCost: 'float',
		steps: 'int',
		qualityPercent: 'int',
		textQualityPercent: 'int',
		structureQualityPercent: 'int',
		layoutQualityPercent: 'int',
		tableQualityPercent: 'int',
		speedPercent: 'int',
		stabilityPercent: 'int',
		priority: 'int',
		monetaryCost: 'float',
		parserChain: 'string',
		routeChain: 'string',
		inputState: 'string',
		outputState: 'string',
		externalCount: 'int',
		lossyCount: 'int'
	};
	const DEFAULT_FILTERS = OPTIONS.defaults || {};
	const FILTER_FIELDS = [
		{ key: 'source_type', label: 'Source', defaultValue: DEFAULT_FILTERS.source_type || 'string', alwaysVisible: true },
		{ key: 'input_state', label: 'Input', defaultValue: DEFAULT_FILTERS.input_state || '', alwaysVisible: true },
		{ key: 'output_state', label: 'Output', defaultValue: DEFAULT_FILTERS.output_state || '', alwaysVisible: true },
		{ key: 'target_type', label: 'Target', defaultValue: DEFAULT_FILTERS.target_type || 'return', alwaysVisible: true },
		{ key: 'strategy', label: 'Strategy', defaultValue: DEFAULT_FILTERS.strategy || 'balanced', alwaysVisible: true },
		{ key: 'allow_external', label: 'External', defaultValue: DEFAULT_FILTERS.allow_external || 'no' },
		{ key: 'allow_lossy', label: 'Lossy', defaultValue: DEFAULT_FILTERS.allow_lossy || 'yes' },
		{ key: 'max_steps', label: 'Max steps', defaultValue: DEFAULT_FILTERS.max_steps || '5' }
	];
	const OPTIONAL_FILTER_FIELDS = FILTER_FIELDS.filter((field) => !field.alwaysVisible);
	const FILTER_DEFAULTS = FILTER_FIELDS.reduce((carry, field) => {
		carry[field.key] = field.defaultValue || '';
		return carry;
	}, {});
	const HOT_FILTER_KEYS = new Set(['source_type', 'input_state', 'output_state', 'target_type', 'strategy']);
	const OPTIONAL_FILTER_PANEL_CLASS = 'parser-explorer-optional-filter-panel';
	const visibleOptionalFilters = new Set();

	const layout = {
		type: 'stack',
		className: 'mg-layout-root',
		children: [
			{
				type: 'zone',
				key: 'topLine1',
				className: 'parser-explorer-panel parser-explorer-panel--main'
			},
			{
				type: 'zone',
				key: 'topLine2',
				className: 'parser-explorer-panel parser-explorer-panel--filters'
			},
			{
				type: 'view',
				key: 'main',
				className: 'parser-explorer-main'
			},
			{
				type: 'zone',
				key: 'statusZone',
				className: 'parser-explorer-panel parser-explorer-panel--status'
			}
		]
	};

	function setLog(message) {
		const logElement = document.querySelector(LOG_SELECTOR);

		if (!logElement) {
			return;
		}

		logElement.replaceChildren();

		const label = document.createElement('strong');
		label.textContent = 'Last action:';

		logElement.appendChild(label);
		logElement.appendChild(document.createTextNode(' ' + getText(message, 'None')));
	}

	function getText(value, placeholder = '-') {
		if (value === null || value === undefined || value === '') {
			return placeholder;
		}

		return String(value);
	}

	function formatPercent(value) {
		if (value === null || value === undefined || value === '') {
			return '-';
		}

		return String(value) + '%';
	}

	function formatCost(value) {
		const number = Number(value);

		if (Number.isNaN(number)) {
			return getText(value);
		}

		return number.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
	}

	function createElement(tagName, className, text = null) {
		const element = document.createElement(tagName);
		element.className = className;

		if (text !== null && text !== undefined) {
			element.textContent = String(text);
		}

		return element;
	}

	function cellStack(mainText, subText = '') {
		const wrapper = createElement('div', 'parser-explorer-cell-stack');
		wrapper.appendChild(createElement('div', 'parser-explorer-cell-main', mainText));

		if (subText !== '') {
			wrapper.appendChild(createElement('div', 'parser-explorer-cell-sub', subText));
		}

		return wrapper;
	}

	function pill(label, className = '') {
		const item = createElement('span', ('parser-explorer-pill ' + className).trim(), label);
		return item;
	}

	function renderCombination(value, row) {
		return cellStack(getText(row.inputState) + ' -> ' + getText(row.outputState), getText(row.sourceType) + ' source -> ' + getText(row.targetType) + ' target');
	}

	function renderParserChain(value, row) {
		return cellStack(getText(row.parserChain), getText(row.routeChain));
	}

	function renderScore(value, row) {
		const wrapper = createElement('div', 'parser-explorer-pill-row');
		wrapper.appendChild(pill('cost ' + formatCost(row.totalCost), 'parser-explorer-pill-strong'));
		wrapper.appendChild(pill('steps ' + getText(row.steps, '0')));
		wrapper.appendChild(pill('quality ' + formatPercent(row.qualityPercent)));
		wrapper.appendChild(pill('speed ' + formatPercent(row.speedPercent)));
		return wrapper;
	}

	function renderQuality(value, row) {
		const wrapper = createElement('div', 'parser-explorer-pill-row');
		wrapper.appendChild(pill('text ' + formatPercent(row.textQualityPercent)));
		wrapper.appendChild(pill('structure ' + formatPercent(row.structureQualityPercent)));
		wrapper.appendChild(pill('layout ' + formatPercent(row.layoutQualityPercent)));
		wrapper.appendChild(pill('table ' + formatPercent(row.tableQualityPercent)));
		return wrapper;
	}

	function renderFlags(value, row) {
		const wrapper = createElement('div', 'parser-explorer-pill-row');
		wrapper.appendChild(pill(getText(row.strategy, 'balanced'), 'parser-explorer-pill-strong'));

		if (Number(row.externalCount || 0) > 0) {
			wrapper.appendChild(pill('external ' + String(row.externalCount), 'parser-explorer-pill-warning'));
		}

		if (Number(row.lossyCount || 0) > 0) {
			wrapper.appendChild(pill('lossy ' + String(row.lossyCount), 'parser-explorer-pill-warning'));
		}

		if (Number(row.monetaryCost || 0) > 0) {
			wrapper.appendChild(pill('cost $ ' + formatCost(row.monetaryCost), 'parser-explorer-pill-warning'));
		}

		return wrapper;
	}

	function buildFilterPayload(filters) {
		const result = Object.assign({}, FILTER_DEFAULTS);

		Object.entries(filters || {}).forEach(([key, value]) => {
			if (value === null || value === undefined || value === '') {
				return;
			}

			result[key] = String(value);
		});

		return result;
	}

	function resolveSortForRequest(request) {
		const sortKey = request.sortKey || 'totalCost';
		const sortDirection = request.sortDirection || 'asc';

		return {
			key: sortKey,
			dir: sortDirection,
			type: SORT_TYPES[sortKey] || 'string'
		};
	}

	async function postJson(payload) {
		const response = await fetch(ENDPOINT_URL, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(payload)
		});

		if (!response.ok) {
			throw new Error('Request failed with status ' + String(response.status));
		}

		return response.json();
	}

	async function loadRemoteDetail(context) {
		const row = context && context.row ? context.row : null;

		if (!row) {
			throw new Error('Missing parser combination row.');
		}

		if (row.detail) {
			return row.detail;
		}

		const state = grid ? grid.getState() : {};
		const response = await postJson({
			mode: 'detail',
			id: row.id || '',
			row,
			filters: buildFilterPayload(state.filters || {})
		});

		if (!response || !response.found || !response.detail) {
			throw new Error('No detail data returned for parser combination.');
		}

		return response.detail;
	}

	function createDetailLoadingPlaceholder(context) {
		return createElement('div', 'parser-explorer-detail-summary', 'Loading parser combination detail...');
	}

	function createDetailErrorPlaceholder(context) {
		return createElement('div', 'parser-explorer-detail-summary', 'Failed to load detail: ' + getText(context && context.error));
	}

	function section(title, content) {
		const wrapper = createElement('div', 'parser-explorer-section');
		wrapper.appendChild(createElement('div', 'parser-explorer-section-title', title));
		wrapper.appendChild(content);
		return wrapper;
	}

	function renderFieldList(rows) {
		const list = createElement('div', 'parser-explorer-field-list');

		(rows || []).forEach((row) => {
			const item = createElement('div', 'parser-explorer-field');
			item.appendChild(createElement('div', 'parser-explorer-field-label', row.label || row.key || 'Value'));
			item.appendChild(createElement('div', 'parser-explorer-field-value', getText(row.value)));
			list.appendChild(item);
		});

		return list;
	}

	function renderSteps(steps) {
		const list = createElement('div', 'parser-explorer-step-list');

		(steps || []).forEach((step) => {
			const item = createElement('div', 'parser-explorer-step');
			item.appendChild(createElement('div', 'parser-explorer-cell-main', String(step.index || '?') + '. ' + getText(step.parserName) + ' / ' + getText(step.routeName)));
			item.appendChild(createElement('div', 'parser-explorer-cell-sub', getText(step.from) + ' -> ' + getText(step.to) + ' | cost ' + formatCost(step.cost)));
			list.appendChild(item);
		});

		return list;
	}

	async function writeClipboardText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			await navigator.clipboard.writeText(text);
			return;
		}

		const textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.setAttribute('readonly', 'readonly');
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();

		try {
			document.execCommand('copy');
		} finally {
			textarea.remove();
		}
	}

	function button(label, onClick) {
		const element = document.createElement('button');
		element.type = 'button';
		element.className = 'parser-explorer-button';
		element.textContent = label;
		element.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			onClick(element);
		});
		return element;
	}

	function renderParserDetail(context) {
		const payload = context && context.payload ? context.payload : {};
		const wrapper = createElement('div', 'parser-explorer-detail');
		const header = createElement('div', 'parser-explorer-detail-header');
		const headerText = createElement('div', 'parser-explorer-cell-stack');
		const actions = createElement('div', 'parser-explorer-detail-actions');

		headerText.appendChild(createElement('div', 'parser-explorer-detail-title', getText(payload.headline, 'Parser combination')));
		headerText.appendChild(createElement('div', 'parser-explorer-detail-summary', getText(payload.summary, '')));
		actions.appendChild(button('Copy PHP', async () => {
			await writeClipboardText(getText(payload.phpCode, ''));
			setLog('Copied PHP parser request to clipboard.');
		}));
		header.appendChild(headerText);
		header.appendChild(actions);
		wrapper.appendChild(header);

		const badgeRow = createElement('div', 'parser-explorer-pill-row');
		(payload.badges || []).forEach((badge) => {
			badgeRow.appendChild(pill(badge.label || '', badge.className || ''));
		});
		wrapper.appendChild(badgeRow);

		const layout = createElement('div', 'parser-explorer-detail-layout');
		const left = createElement('div', 'parser-explorer-detail-left');
		const right = createElement('div', 'parser-explorer-detail-right');

		left.appendChild(section('Plan', renderFieldList(payload.sections || [])));
		left.appendChild(section('Steps', renderSteps(payload.steps || [])));

		const code = document.createElement('pre');
		code.className = 'parser-explorer-code';
		code.textContent = getText(payload.phpCode, '');
		right.appendChild(section('PHP request', code));

		layout.appendChild(left);
		layout.appendChild(right);
		wrapper.appendChild(layout);

		return wrapper;
	}

	function getFilterFieldByKey(key) {
		return FILTER_FIELDS.find((field) => field.key === key) || null;
	}

	function getFilterFieldByLabel(label) {
		return FILTER_FIELDS.find((field) => field.label === label) || null;
	}

	function getFilterControlFromGroup(group) {
		const control = group.querySelector('select, input');

		if (control instanceof HTMLSelectElement || control instanceof HTMLInputElement) {
			return control;
		}

		return null;
	}

	function getControlValue(control) {
		return control ? String(control.value || '') : '';
	}

	function isFilterValueDefault(key, value) {
		return String(value || '') === String(FILTER_DEFAULTS[key] || '');
	}

	function getFilterKeyFromGroup(group) {
		const control = getFilterControlFromGroup(group);

		if (control) {
			const key = control.getAttribute('name')
				|| control.dataset.key
				|| control.dataset.filterKey
				|| control.dataset.fieldKey
				|| '';

			if (key && getFilterFieldByKey(key)) {
				return key;
			}
		}

		const label = group.querySelector('.mg-label');
		const labelText = label ? label.textContent.trim() : '';
		const field = getFilterFieldByLabel(labelText);

		return field ? field.key : '';
	}

	function dispatchFilterControlChanged(control) {
		control.dispatchEvent(new Event('input', { bubbles: true }));
		control.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function resetFilterGroup(group, key) {
		const control = getFilterControlFromGroup(group);

		if (!control) {
			return;
		}

		control.value = String(FILTER_DEFAULTS[key] || '');
		dispatchFilterControlChanged(control);
	}

	function getFilterPanel(root) {
		return root.querySelector('.parser-explorer-panel--filters');
	}

	function getTopPanel(root) {
		return root.querySelector('.parser-explorer-panel--main');
	}

	function getFilterPanels(root) {
		return [getFilterPanel(root), getTopPanel(root)].filter((panel) => panel !== null);
	}

	function ensureOptionalFilterPicker(root) {
		const topPanel = getTopPanel(root);

		if (!topPanel) {
			return null;
		}

		let picker = topPanel.querySelector('.parser-explorer-optional-filter-picker');

		if (picker) {
			return picker;
		}

		picker = document.createElement('label');
		picker.className = 'mg-control-group parser-explorer-optional-filter-picker';

		const label = document.createElement('span');
		label.className = 'mg-label';
		label.textContent = 'Add filter';

		const select = document.createElement('select');
		select.className = 'mg-select';

		picker.appendChild(label);
		picker.appendChild(select);
		topPanel.appendChild(picker);

		select.addEventListener('change', () => {
			const key = select.value;

			if (key !== '') {
				visibleOptionalFilters.add(key);
				applyOptionalFilterVisibility(root);
			}

			select.value = '';
		});

		return picker;
	}

	function updateOptionalFilterPickerOptions(root) {
		const picker = ensureOptionalFilterPicker(root);
		const select = picker ? picker.querySelector('select') : null;

		if (!(select instanceof HTMLSelectElement)) {
			return;
		}

		const optionKeys = OPTIONAL_FILTER_FIELDS
			.filter((field) => !visibleOptionalFilters.has(field.key))
			.map((field) => field.key);
		const signature = optionKeys.join('|');

		if (select.dataset.signature === signature) {
			return;
		}

		const currentValue = select.value;
		select.dataset.signature = signature;
		select.replaceChildren();

		const placeholder = document.createElement('option');
		placeholder.value = '';
		placeholder.textContent = 'Add filter';
		select.appendChild(placeholder);

		optionKeys.forEach((key) => {
			const field = getFilterFieldByKey(key);

			if (!field) {
				return;
			}

			const option = document.createElement('option');
			option.value = field.key;
			option.textContent = field.label;
			select.appendChild(option);
		});

		select.value = optionKeys.includes(currentValue) ? currentValue : '';
	}

	function ensureOptionalFilterRemoveButton(group, key, root) {
		if (group.querySelector('.parser-explorer-optional-filter-remove')) {
			return;
		}

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'parser-explorer-optional-filter-remove';
		remove.title = 'Remove this filter';
		remove.textContent = '×';
		remove.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			resetFilterGroup(group, key);
			visibleOptionalFilters.delete(key);
			applyOptionalFilterVisibility(root);
		});

		group.appendChild(remove);
	}

	function moveGroupToPanel(group, panel) {
		if (!panel || group.parentElement === panel) {
			return;
		}

		panel.appendChild(group);
	}

	function getAllFilterGroups(root) {
		const groups = [];

		getFilterPanels(root).forEach((panel) => {
			Array.from(panel.querySelectorAll('.mg-control-group')).forEach((group) => {
				if (group.classList.contains('parser-explorer-optional-filter-picker')) {
					return;
				}

				if (!groups.includes(group)) {
					groups.push(group);
				}
			});
		});

		return groups;
	}

	function applyOptionalFilterVisibility(root) {
		const filterPanel = getFilterPanel(root);
		const topPanel = getTopPanel(root);

		if (!filterPanel || !topPanel) {
			return;
		}

		ensureOptionalFilterPicker(root);

		getAllFilterGroups(root).forEach((group) => {
			const key = getFilterKeyFromGroup(group);
			const field = key !== '' ? getFilterFieldByKey(key) : null;

			if (!field) {
				return;
			}

			const control = getFilterControlFromGroup(group);
			const value = getControlValue(control);
			const hasNonDefaultValue = !isFilterValueDefault(key, value);
			const className = 'parser-explorer-filter-' + key.replace(/_/g, '-');

			group.classList.add(className);

			if (field.alwaysVisible || HOT_FILTER_KEYS.has(key)) {
				moveGroupToPanel(group, filterPanel);
				group.style.display = '';
				return;
			}

			if (hasNonDefaultValue) {
				visibleOptionalFilters.add(key);
			}

			ensureOptionalFilterRemoveButton(group, key, root);

			if (visibleOptionalFilters.has(key)) {
				moveGroupToPanel(group, topPanel);
				group.style.display = '';
				return;
			}

			moveGroupToPanel(group, filterPanel);
			group.style.display = 'none';
		});

		updateOptionalFilterPickerOptions(root);
	}

	function applyDefaultFilterControlValues(root) {
		getFilterPanels(root).forEach((panel) => {
			Array.from(panel.querySelectorAll('.mg-control-group')).forEach((group) => {
				const key = getFilterKeyFromGroup(group);
				const control = getFilterControlFromGroup(group);

				if (!key || !control || String(control.value || '') !== '') {
					return;
				}

				const defaultValue = String(FILTER_DEFAULTS[key] || '');

				if (defaultValue === '') {
					return;
				}

				control.value = defaultValue;
			});
		});
	}

	function initializeOptionalFilterControls(root) {
		const filterPanel = getFilterPanel(root);
		const topPanel = getTopPanel(root);

		if (!filterPanel || !topPanel || root.dataset.optionalFiltersInitialized === '1') {
			return;
		}

		root.dataset.optionalFiltersInitialized = '1';
		applyOptionalFilterVisibility(root);

		[filterPanel, topPanel].forEach((panel) => {
			panel.addEventListener('change', () => {
				window.requestAnimationFrame(() => {
					applyOptionalFilterVisibility(root);
				});
			});
		});
	}

	let grid = null;

	(async function() {
		const root = document.querySelector(GRID_SELECTOR);

		if (!root || root.dataset.initialized === '1') {
			return;
		}

		root.dataset.initialized = '1';

		const adapter = new AjaxAdapter({
			url: ENDPOINT_URL,
			method: 'POST',
			rowsPath: 'data',
			totalPath: 'total',
			mapRequest(request) {
				const state = grid ? grid.getState() : {};
				const filters = buildFilterPayload(state.filters || {});
				const sort = resolveSortForRequest(request);

				return {
					mode: 'page',
					page: request.page || 1,
					pageSize: request.pageSize || BATCH_SIZE,
					search: request.search || state.query?.search || '',
					sort: [sort],
					filters
				};
			}
		});

		grid = new ModularGrid(GRID_SELECTOR, {
			layout,
			adapter,
			dataMode: 'server',
			server: {
				searchDebounceMs: <?php echo (int) ($this->_['searchDebounceMs'] ?? 700); ?>,
				watchStateKeys: ['query', 'filters']
			},
			features: {
				paging: false
			},
			pageSize: BATCH_SIZE,
			sort: {
				key: 'totalCost',
				direction: 'asc'
			},
			plugins: [
				SearchPlugin,
				FiltersPlugin,
				HeaderMenuPlugin,
				InfoPlugin,
				RowActionsPlugin,
				ColumnVisibilityPlugin,
				ResetPlugin,
				SessionStoragePlugin,
				RowDetailPlugin,
			],
			pluginOptions: {
				search: {
					zone: 'topLine1',
					order: 10,
					label: 'Search',
					placeholder: 'Search within selected conversion'
				},
				filters: {
					zone: 'topLine2',
					order: 10,
					stateKey: 'filters',
					showClearButton: false,
					clearLabel: 'Clear filters',
					fields: [
						{
							key: 'source_type',
							defaultValue: FILTER_DEFAULTS.source_type || '',
							label: 'Source',
							type: 'select',
							options: OPTIONS.sourceTypes || []
						},
						{
							key: 'input_state',
							defaultValue: FILTER_DEFAULTS.input_state || '',
							label: 'Input',
							type: 'select',
							options: OPTIONS.inputStates || []
						},
						{
							key: 'output_state',
							defaultValue: FILTER_DEFAULTS.output_state || '',
							label: 'Output',
							type: 'select',
							options: OPTIONS.outputStates || []
						},
						{
							key: 'target_type',
							defaultValue: FILTER_DEFAULTS.target_type || '',
							label: 'Target',
							type: 'select',
							options: OPTIONS.targetTypes || []
						},
						{
							key: 'strategy',
							defaultValue: FILTER_DEFAULTS.strategy || '',
							label: 'Strategy',
							type: 'select',
							options: OPTIONS.strategies || []
						},
						{
							key: 'allow_external',
							defaultValue: FILTER_DEFAULTS.allow_external || '',
							label: 'External',
							type: 'select',
							options: OPTIONS.boolean || []
						},
						{
							key: 'allow_lossy',
							defaultValue: FILTER_DEFAULTS.allow_lossy || '',
							label: 'Lossy',
							type: 'select',
							options: OPTIONS.boolean || []
						},
						{
							key: 'max_steps',
							defaultValue: FILTER_DEFAULTS.max_steps || '',
							label: 'Max steps',
							type: 'select',
							options: OPTIONS.maxSteps || []
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
					order: 20,
					label: 'Reset',
					sections: ['query', 'filters', 'columns']
				},
				sessionStorage: {
					key: 'parseflow-parser-explorer-grid-v5',
					sections: ['query', 'filters', 'columns']
				},
				info: {
					zone: 'statusZone',
					order: 10,
					displayMode: 'loaded'
				},
				rowActions: {
					headerMenu: {
						enabled: true,
						buttonLabel: '...',
						items: [
							{
								type: 'columnVisibility',
								label: 'Columns',
								showReset: true,
								resetLabel: 'Reset columns'
							}
						]
					},
					items: [
						{
							key: 'copy-php',
							label: 'Copy PHP request',
							onClick: async (context) => {
								const detail = await loadRemoteDetail(context);
								await writeClipboardText(getText(detail.phpCode, ''));
								setLog('Copied PHP parser request to clipboard.');
							}
						}
					]
				},
				rowDetail: {
					rowIdKey: 'id',
					clearOnDataReload: true,
					asyncDetail: {
						load(context) {
							return loadRemoteDetail(context);
						},
						renderLoading(context) {
							return createDetailLoadingPlaceholder(context);
						},
						renderError(context) {
							return createDetailErrorPlaceholder(context);
						},
						render(context) {
							return renderParserDetail(context);
						}
					}
				}
			},
			columns: [
				{
					key: 'inputState',
					label: 'Combination',
					width: 310,
					headerMenu: {
						defaultSortKey: 'inputState',
						defaultSortDirection: 'asc',
						sortOptions: [
							{ key: 'inputState', label: 'Input' },
							{ key: 'outputState', label: 'Output' },
							{ key: 'totalCost', label: 'Cost' },
							{ key: 'steps', label: 'Steps' }
						]
					},
					render(value, row) {
						return renderCombination(value, row);
					}
				},
				{
					key: 'parserChain',
					label: 'Parser chain',
					width: 430,
					textDisplay: {
						strategy: 'clamp',
						lines: 3,
						expandable: true
					},
					headerMenu: {
						defaultSortKey: 'parserChain',
						defaultSortDirection: 'asc',
						sortOptions: [
							{ key: 'parserChain', label: 'Parser chain' },
							{ key: 'routeChain', label: 'Route chain' },
							{ key: 'totalCost', label: 'Cost' },
							{ key: 'qualityPercent', label: 'Combined quality' }
						]
					},
					render(value, row) {
						return renderParserChain(value, row);
					}
				},
				{
					key: 'totalCost',
					label: 'Score',
					width: 270,
					headerMenu: {
						defaultSortKey: 'totalCost',
						defaultSortDirection: 'asc',
						sortOptions: [
							{ key: 'totalCost', label: 'Cost' },
							{ key: 'steps', label: 'Steps' },
							{ key: 'qualityPercent', label: 'Combined quality' },
							{ key: 'speedPercent', label: 'Speed' },
							{ key: 'stabilityPercent', label: 'Stability' }
						]
					},
					render(value, row) {
						return renderScore(value, row);
					}
				},
				{
					key: 'qualityPercent',
					label: 'Quality',
					width: 320,
					headerMenu: {
						defaultSortKey: 'qualityPercent',
						defaultSortDirection: 'desc',
						sortOptions: [
							{ key: 'qualityPercent', label: 'Combined quality' },
							{ key: 'textQualityPercent', label: 'Text quality' },
							{ key: 'structureQualityPercent', label: 'Structure quality' },
							{ key: 'layoutQualityPercent', label: 'Layout quality' },
							{ key: 'tableQualityPercent', label: 'Table quality' }
						]
					},
					render(value, row) {
						return renderQuality(value, row);
					}
				},
				{
					key: 'strategy',
					label: 'Flags',
					width: 230,
					headerMenu: {
						defaultSortKey: 'externalCount',
						defaultSortDirection: 'desc',
						sortOptions: [
							{ key: 'externalCount', label: 'External count' },
							{ key: 'lossyCount', label: 'Lossy count' },
							{ key: 'monetaryCost', label: 'Monetary cost' },
							{ key: 'priority', label: 'Priority' }
						]
					},
					render(value, row) {
						return renderFlags(value, row);
					}
				},
				{
					key: 'routeChain',
					label: 'Route chain',
					width: 420,
					visible: false,
					textDisplay: {
						strategy: 'clamp',
						lines: 3,
						expandable: true
					},
					headerMenu: {
						defaultSortKey: 'routeChain',
						defaultSortDirection: 'asc',
						sortOptions: [
							{ key: 'routeChain', label: 'Route chain' }
						]
					}
				},
				{
					key: 'id',
					label: 'ID',
					width: 140,
					visible: false
				}
			]
		});

		grid.on('data:appended', ({ appendedCount, totalLoaded }) => {
			setLog('Loaded ' + String(appendedCount) + ' more parser combinations. ' + String(totalLoaded) + ' rows are currently loaded.');
		});

		grid.on('detail:loaded', (event) => {
			const row = event && typeof event === 'object' ? event.row : null;
			setLog('Loaded detail for ' + getText(row && row.inputState) + ' -> ' + getText(row && row.outputState));
		});

		grid.on('detail:error', (event) => {
			const detailError = event && typeof event === 'object' ? event.error : null;
			setLog('Failed to load detail: ' + getText(detailError));
		});

		await grid.init();
		applyDefaultFilterControlValues(root);
		initializeOptionalFilterControls(root);
		setLog('Parser explorer initialized. Showing a bounded top result set for the selected input/output conversion.');
	})();
</script>
