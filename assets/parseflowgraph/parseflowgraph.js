(function () {
	'use strict';

	function text(value) {
		return value === null || value === undefined ? '' : String(value);
	}

	function matches(value, needle) {
		return text(value).toLowerCase().indexOf(needle) !== -1;
	}

	function unique(values) {
		return Array.from(new Set(values.filter(Boolean))).sort(function (a, b) {
			return a.localeCompare(b);
		});
	}

	function option(value, label) {
		var item = document.createElement('option');
		item.value = value;
		item.textContent = label;
		return item;
	}

	function clear(node) {
		while (node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function setStats(root, data, visibleNodes, visibleEdges, visibleRoutes) {
		var stats = root.querySelector('[data-role="stats"]');
		clear(stats);
		[
			['Parsers', data.parsers ? data.parsers.length : 0],
			['Routes', data.routes ? data.routes.length : 0],
			['States', data.nodes ? data.nodes.length : 0],
			['Visible', visibleNodes.length + ' / ' + visibleEdges.length + ' / ' + visibleRoutes.length]
		].forEach(function (row) {
			var item = document.createElement('span');
			item.className = 'parseflow-graph__stat';
			item.textContent = row[0] + ': ' + row[1];
			stats.appendChild(item);
		});
	}

	function createSvg(tag) {
		return document.createElementNS('http://www.w3.org/2000/svg', tag);
	}

	function renderGraph(root, nodes, edges) {
		var canvas = root.querySelector('[data-role="canvas"]');
		clear(canvas);

		if (nodes.length === 0) {
			return;
		}

		var width = Math.max(980, nodes.length * 160);
		var rowHeight = 110;
		var height = Math.max(300, Math.ceil(nodes.length / 5) * rowHeight + 80);
		var positions = {};
		var svg = createSvg('svg');
		svg.setAttribute('class', 'parseflow-graph__svg');
		svg.setAttribute('width', String(width));
		svg.setAttribute('height', String(height));
		svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

		nodes.forEach(function (node, index) {
			var col = index % 5;
			var row = Math.floor(index / 5);
			positions[node.id] = {
				x: 40 + col * 185,
				y: 40 + row * rowHeight
			};
		});

		edges.forEach(function (edge) {
			var from = positions[edge.from];
			var to = positions[edge.to];
			if (!from || !to) {
				return;
			}

			var line = createSvg('line');
			line.setAttribute('class', 'parseflow-graph__edge');
			line.setAttribute('x1', String(from.x + 150));
			line.setAttribute('y1', String(from.y + 28));
			line.setAttribute('x2', String(to.x));
			line.setAttribute('y2', String(to.y + 28));
			svg.appendChild(line);

			var label = createSvg('text');
			label.setAttribute('class', 'parseflow-graph__edge-label');
			label.setAttribute('x', String((from.x + to.x + 150) / 2));
			label.setAttribute('y', String((from.y + to.y + 56) / 2 - 4));
			label.textContent = edge.parserName;
			svg.appendChild(label);
		});

		nodes.forEach(function (node) {
			var pos = positions[node.id];
			var group = createSvg('g');
			group.setAttribute('class', 'parseflow-graph__node');
			group.setAttribute('transform', 'translate(' + pos.x + ' ' + pos.y + ')');

			var rect = createSvg('rect');
			rect.setAttribute('rx', '10');
			rect.setAttribute('ry', '10');
			rect.setAttribute('width', '150');
			rect.setAttribute('height', '58');
			group.appendChild(rect);

			var label = createSvg('text');
			label.setAttribute('x', '12');
			label.setAttribute('y', '24');
			label.textContent = node.label.length > 22 ? node.label.slice(0, 21) + '...' : node.label;
			group.appendChild(label);

			var type = createSvg('text');
			type.setAttribute('class', 'parseflow-graph__node-type');
			type.setAttribute('x', '12');
			type.setAttribute('y', '43');
			type.textContent = node.type;
			group.appendChild(type);

			svg.appendChild(group);
		});

		canvas.appendChild(svg);
	}

	function renderGrid(root, routes) {
		var grid = root.querySelector('[data-role="grid"]');
		clear(grid);

		if (routes.length === 0) {
			return;
		}

		var table = document.createElement('table');
		var thead = document.createElement('thead');
		var tbody = document.createElement('tbody');
		var headRow = document.createElement('tr');

		['Parser', 'Route', 'From', 'To', 'Text', 'Structure', 'Image', 'Speed', 'Stability'].forEach(function (heading) {
			var th = document.createElement('th');
			th.textContent = heading;
			headRow.appendChild(th);
		});

		thead.appendChild(headRow);
		table.appendChild(thead);

		routes.forEach(function (route) {
			var tr = document.createElement('tr');
			[
				route.parserName,
				route.routeName,
				route.from,
				route.to,
				route.textQuality,
				route.structureQuality,
				route.imageQuality,
				route.speed,
				route.stability
			].forEach(function (value) {
				var td = document.createElement('td');
				td.textContent = text(value);
				tr.appendChild(td);
			});
			tbody.appendChild(tr);
		});

		table.appendChild(tbody);
		grid.appendChild(table);
	}

	function render(root, data) {
		var search = root.querySelector('[data-role="search"]');
		var parserFilter = root.querySelector('[data-role="parser-filter"]');
		var typeFilter = root.querySelector('[data-role="type-filter"]');
		var message = root.querySelector('[data-role="message"]');
		var needle = text(search.value).toLowerCase();
		var parser = parserFilter.value;
		var type = typeFilter.value;
		var routes = data.routes || [];
		var edges = data.edges || [];
		var nodes = data.nodes || [];
		var visibleRoutes = routes.filter(function (route) {
			if (parser && route.parserName !== parser) {
				return false;
			}

			if (needle && !matches(route.parserName + ' ' + route.routeName + ' ' + route.from + ' ' + route.to, needle)) {
				return false;
			}

			return true;
		});
		var visibleRouteKeys = new Set(visibleRoutes.map(function (route) {
			return route.parserName + ':' + route.routeName;
		}));
		var visibleEdges = edges.filter(function (edge) {
			return visibleRouteKeys.has(edge.parserName + ':' + edge.routeName);
		});
		var nodeIds = new Set();
		visibleEdges.forEach(function (edge) {
			nodeIds.add(edge.from);
			nodeIds.add(edge.to);
		});
		var visibleNodes = nodes.filter(function (node) {
			if (!nodeIds.has(node.id)) {
				return false;
			}

			if (type && node.type !== type) {
				return false;
			}

			if (needle && !matches(node.label + ' ' + node.type + ' ' + node.format, needle)) {
				return Array.from(visibleEdges).some(function (edge) {
					return (edge.from === node.id || edge.to === node.id) && matches(edge.parserName + ' ' + edge.routeName, needle);
				});
			}

			return true;
		});

		var allowedNodeIds = new Set(visibleNodes.map(function (node) {
			return node.id;
		}));
		visibleEdges = visibleEdges.filter(function (edge) {
			return allowedNodeIds.has(edge.from) && allowedNodeIds.has(edge.to);
		});

		setStats(root, data, visibleNodes, visibleEdges, visibleRoutes);
		renderGraph(root, visibleNodes, visibleEdges);
		renderGrid(root, visibleRoutes);

		if (!nodes.length) {
			message.textContent = 'No graph nodes were returned by the JSON endpoint. Check whether ClassMap discovers ParseFlow\\Api\\IParser implementations.';
		} else if (!visibleNodes.length) {
			message.textContent = 'No graph nodes match the current filters.';
		} else {
			message.textContent = '';
		}
	}

	function init(root) {
		var url = root.getAttribute('data-graph-url');
		var message = root.querySelector('[data-role="message"]');

		fetch(url, { headers: { Accept: 'application/json' } })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				var parserFilter = root.querySelector('[data-role="parser-filter"]');
				var typeFilter = root.querySelector('[data-role="type-filter"]');
				unique((data.routes || []).map(function (route) { return route.parserName; })).forEach(function (parser) {
					parserFilter.appendChild(option(parser, parser));
				});
				unique((data.nodes || []).map(function (node) { return node.type; })).forEach(function (type) {
					typeFilter.appendChild(option(type, type));
				});

				['search', 'parser-filter', 'type-filter'].forEach(function (role) {
					root.querySelector('[data-role="' + role + '"]').addEventListener('input', function () {
						render(root, data);
					});
				});

				render(root, data);
			})
			.catch(function (error) {
				message.textContent = 'Could not load ParseFlow graph data: ' + error.message;
			});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-parseflow-graph]').forEach(init);
	});
}());
