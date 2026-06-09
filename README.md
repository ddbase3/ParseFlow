# ParseFlow

ParseFlow is a BASE3 plugin that provides a graph-based parser service. Parser implementations describe directed routes from one semantic `ParserState` to another. The service discovers active parsers through `IClassMap`, plans the cheapest valid path, executes every step and writes the final payload to the requested target.

## Architecture decision

Interfaces, DTOs, models and exceptions are part of this package under the `ParseFlow` namespace. No `MediaFoundation` namespace is used in this implementation.

## Parser folders

```text
ParseFlow/src/Parser
├── Core
├── Common
├── Html
├── Markdown
├── Csv
├── Json
├── Xml
├── IniEnv
├── Office
└── Image
```

## Runtime requirements

Most parsers use PHP core functions only. Some parser groups are guarded by `requirements()` and `isAvailable()`:

- `dom`: HTML, XML output, SVG and several XML-family parsers
- `simplexml`: XML-family parsers
- `zip`: DOCX, XLSX and PPTX parsers
- `gd`: PNG/JPEG/WebP conversion and thumbnail parsers
- `exif`: JPEG EXIF parser

Unavailable parsers remain discoverable but evaluate as unsupported, so the planner can choose another route.

## Capability exploration

```php
$report = $parserService->explore();
```

The report contains parser names, route information, known graph states, reachable outputs and optionally a suggested plan for a concrete request.

## Included parser count

This package includes 91 concrete parser classes and 91 routes.

See `docs/ParserCatalog.md` for the generated parser catalog.

## Graph UI endpoint

`ParseFlow\Output\ParseFlowGraphOutput` implements `Base3\Api\IOutput` and exposes two formats:

- `html`: renders the graph container, filter toolbar, SVG graph and route grid.
- `json`: returns graph data with parsers, states, nodes, edges and routes.

The HTML output resolves assets through `Base3\Api\IAssetResolver` and builds the JSON endpoint through `Base3\LinkTarget\Api\ILinkTargetService`:

```php
$this->assetResolver->resolve('plugin/ParseFlow/assets/parseflowgraph/parseflowgraph.js');
$this->linkTargetService->getLink([
	'name' => self::getName(),
	'out' => 'json'
]);
```

The output name is `parseflowgraphoutput`.
