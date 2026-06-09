# ParseFlow

Graph-based parser service for BASE3, discovering modular parser routes and planning efficient `Source + Input -> Output + Target` conversions with scoring, strategies, execution, and an admin explorer.

## Overview

ParseFlow is a BASE3 plugin that provides a graph-based parser and transformation service.

Instead of binding parser logic to one specific file type or one fixed conversion, ParseFlow models every parser capability as a directed graph edge:

```text
Source + Input -> Parser Graph -> Output + Target
```

A caller describes:

* where the data comes from: `Source`
* how the data should be interpreted: `Input`
* what representation should be produced: `Output`
* where the result should go: `Target`

ParseFlow then discovers all available parsers, builds a route graph, selects the best parser chain according to a strategy, executes the chain, and writes or returns the result.

## Key features

* BASE3 plugin with DI registration
* Parser discovery through `IClassMap`
* Modular parser interface
* Directed parser route graph
* Multi-step parser planning
* Weighted scoring and route quality profiles
* Cycle-safe planner
* Source/Input/Output/Target model
* Parser execution pipeline
* Target writer for return, file, directory and stream targets
* Parser capability exploration
* ModularGrid-based admin explorer
* Extensible parser packs
* Smoke test entry point
* Documentation-oriented parser structure

## Design goals

ParseFlow is designed to make parser workflows explicit, inspectable and extensible.

The main goals are:

* keep parser classes small and reusable
* avoid hard-coded parser chains
* allow automatic fallback routes
* allow different strategies such as fastest or best quality
* make every conversion path explainable
* support additional parser plugins without changing the central service
* keep BASE3 conventions intact

## BASE3 integration

ParseFlow follows BASE3 conventions:

* one plugin class registers the service
* services are registered through the BASE3 container
* parser implementations are discovered through `IClassMap`
* parser names are technical lowercase names returned by `getName()`
* no Composer autoloading is required inside the plugin
* classes live below `src/`
* namespaces mirror the directory structure
* UI displays implement BASE3 output/display interfaces

Typical plugin location:

```text
components/Base3/ParseFlow
```

## Important implementation decision

This implementation keeps parser interfaces, DTOs, source/input/output/target models and exceptions inside the `ParseFlow` namespace.

That means the parser API is part of this plugin:

```text
ParseFlow\Api
ParseFlow\Dto
ParseFlow\Exception
ParseFlow\Source
ParseFlow\Input
ParseFlow\Output
ParseFlow\Target
```

This differs from the earlier architecture draft where some contracts were assigned to `MediaFoundation`.

## Main architecture

### Four axes

ParseFlow separates parser requests into four explicit axes.

| Axis   | Meaning                                         | Examples                                      |
| ------ | ----------------------------------------------- | --------------------------------------------- |
| Source | Where the original data comes from              | file, stream, string, binary                  |
| Input  | How the source should be interpreted            | document/pdf, document/docx, string/html      |
| Output | What semantic representation should be produced | string/text, string/markdown, structured/json |
| Target | Where the final result should be delivered      | return, file, directory, stream               |

Example:

```text
FileParserSource('/tmp/input.docx')
+ DocumentParserInput('docx')
-> parser graph
-> StringParserOutput('markdown')
+ ReturnParserTarget()
```

### Graph model

Parser routes are graph edges.

A route has:

* parser name
* route name
* source state
* target state
* route quality
* features
* requirements
* options

Example route:

```text
document/docx -- docxtomarkdownparser --> string/markdown
```

Multi-step conversion is possible:

```text
document/docx
-> string/html
-> string/markdown
```

The caller does not need to know this chain in advance.

## Directory structure

Typical plugin structure:

```text
ParseFlow
├── LICENSE
├── README.md
├── VERSION
├── MANIFEST.txt
├── smoke.php
├── src
│   ├── ParseFlowPlugin.php
│   ├── Api
│   │   ├── IParser.php
│   │   ├── IParserInput.php
│   │   ├── IParserOutput.php
│   │   ├── IParserService.php
│   │   ├── IParserSource.php
│   │   └── IParserTarget.php
│   ├── Dto
│   │   ├── ParserCapabilityReport.php
│   │   ├── ParserExploreRequest.php
│   │   ├── ParserPayload.php
│   │   ├── ParserPlan.php
│   │   ├── ParserPlanningContext.php
│   │   ├── ParserPlanStep.php
│   │   ├── ParserRequest.php
│   │   ├── ParserResult.php
│   │   ├── ParserRoute.php
│   │   ├── ParserRouteEvaluation.php
│   │   ├── ParserRouteQuality.php
│   │   ├── ParserSourceInspection.php
│   │   ├── ParserState.php
│   │   ├── ParserStepRequest.php
│   │   ├── ParserStepResult.php
│   │   └── ParserStrategy.php
│   ├── Exception
│   ├── Source
│   ├── Input
│   ├── Output
│   ├── Target
│   ├── Parser
│   ├── Service
│   └── Display
└── tpl
    └── Display
```

## Core service

The central service is:

```php
ParseFlow\Api\IParserService
```

Default implementation:

```php
ParseFlow\Service\DefaultParserService
```

Main methods:

```php
public function parse(ParserRequest $request): ParserResult;
public function plan(ParserRequest $request): ParserPlan;
public function supports(ParserRequest $request): bool;
public function listRoutes(): array;
public function explore(?ParserExploreRequest $request = null): ParserCapabilityReport;
```

## Plugin registration

`ParseFlowPlugin` registers the parser service in the BASE3 container.

Conceptually:

```php
$this->container
	->set(self::getName(), $this, IContainer::SHARED)
	->set(IParserService::class, fn($c) => new DefaultParserService(
		$c->get(IClassMap::class)
	), IContainer::SHARED);
```

The parser service uses `IClassMap` to discover all classes implementing `IParser`.

## Parser discovery

Parser implementations are not manually registered in a central list.

Instead, ParseFlow uses:

```php
$IClassMap->getInstancesByInterface(IParser::class)
```

Every parser class must implement `ParseFlow\Api\IParser`.

The technical parser name is returned by `getName()`.

Example:

```php
public static function getName(): string {
	return 'docxtomarkdownparser';
}
```

The convention is:

* use the simple class name
* lowercase
* no namespace
* no random aliases

## Parser lifecycle

A parser has three main responsibilities:

1. declare available routes
2. evaluate a route for a concrete planning context
3. execute one planned step

Interface shape:

```php
interface IParser extends IBase {

	/**
	 * @return ParserRoute[]
	 */
	public function getRoutes(): array;

	public function evaluate(ParserRoute $route, ParserPlanningContext $context): ParserRouteEvaluation;

	public function execute(ParserStepRequest $request): ParserStepResult;
}
```

## Parser request model

A parser request describes the whole conversion.

Example:

```php
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Target\ReturnParserTarget;

$request = new ParserRequest(
	source: new FileParserSource('/tmp/input.docx'),
	input: new DocumentParserInput('docx'),
	output: new StringParserOutput('markdown'),
	target: new ReturnParserTarget()
);

$result = $parserService->parse($request);
```

## Sources

Sources describe where original data comes from.

Common classes:

```text
ParseFlow\Source\FileParserSource
ParseFlow\Source\StringParserSource
ParseFlow\Source\BinaryParserSource
ParseFlow\Source\StreamParserSource
```

Examples:

```php
new FileParserSource('/tmp/document.pdf');
new StringParserSource('<h1>Hello</h1>');
new BinaryParserSource($binary, ['filename' => 'image.png']);
new StreamParserSource($stream, ['filename' => 'upload.docx']);
```

A source is transport-related. It does not define the semantic graph state by itself.

## Inputs

Inputs describe how the source should be interpreted.

Common classes:

```text
ParseFlow\Input\AutoDetectParserInput
ParseFlow\Input\DocumentParserInput
ParseFlow\Input\ImageParserInput
ParseFlow\Input\PlainTextParserInput
ParseFlow\Input\StructuredParserInput
```

Examples:

```php
new AutoDetectParserInput();
new DocumentParserInput('pdf');
new DocumentParserInput('docx');
new PlainTextParserInput('html');
new StructuredParserInput('json');
```

## Outputs

Outputs describe what semantic representation should be produced.

Common classes:

```text
ParseFlow\Output\StringParserOutput
ParseFlow\Output\StructuredParserOutput
ParseFlow\Output\FilesParserOutput
ParseFlow\Output\ChunksParserOutput
ParseFlow\Output\BinaryParserOutput
ParseFlow\Output\StreamParserOutput
ParseFlow\Output\ImageParserOutput
```

Examples:

```php
new StringParserOutput('text');
new StringParserOutput('markdown');
new StructuredParserOutput('json');
new FilesParserOutput('png');
new ChunksParserOutput('rag');
```

## Targets

Targets describe where the final result should be delivered.

Common classes:

```text
ParseFlow\Target\ReturnParserTarget
ParseFlow\Target\FileParserTarget
ParseFlow\Target\DirectoryParserTarget
ParseFlow\Target\StreamParserTarget
```

Examples:

```php
new ReturnParserTarget();
new FileParserTarget('/tmp/output.md');
new DirectoryParserTarget('/tmp/output');
new StreamParserTarget($stream);
```

Targets are not graph nodes. The graph ends at the requested `Output`. The target only receives or writes the final payload.

## Parser states

The graph uses `ParserState` nodes.

A state describes a semantic representation:

```php
new ParserState(type: 'document', format: 'pdf');
new ParserState(type: 'string', format: 'text');
new ParserState(type: 'string', format: 'markdown');
new ParserState(type: 'structured', format: 'json');
```

State labels are commonly shown as:

```text
document/pdf
string/text
string/markdown
structured/json
```

A state is different from a source or target.

For example:

```text
FileParserSource('/tmp/input.pdf') -> source transport
document/pdf                     -> parser graph state
```

## Parser routes

A parser route is one directed graph edge.

Example:

```php
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;

new ParserRoute(
	parserName: self::getName(),
	routeName: 'docx_to_markdown',
	from: new ParserState('document', 'docx'),
	to: new ParserState('string', 'markdown'),
	quality: new ParserRouteQuality(
		textQuality: 1.0,
		structureQuality: 0.95,
		layoutQuality: 0.85,
		tableQuality: 0.9,
		speed: 0.9,
		stability: 0.95,
		priority: 100
	)
);
```

## Route quality

Every route declares a quality profile.

Important dimensions:

| Field                   | Meaning                                      |
| ----------------------- | -------------------------------------------- |
| textQuality             | Text extraction or text preservation quality |
| structureQuality        | Structural fidelity                          |
| layoutQuality           | Layout preservation                          |
| tableQuality            | Table preservation                           |
| imageQuality            | Image fidelity                               |
| semanticQuality         | Semantic representation quality              |
| speed                   | Expected performance                         |
| stability               | Expected reliability                         |
| monetaryCost            | External or paid execution cost              |
| lossy                   | Whether the route loses information          |
| requiresExternalService | Whether an external service is required      |
| priority                | Stable tie-breaking hint                     |

A single global quality number is intentionally avoided. Different conversions care about different dimensions.

## Planning strategy

`ParserStrategy` controls route selection.

Common modes:

```text
balanced
fastest
best_quality
best_text
best_structure
local_only
```

Typical options:

```php
new ParserStrategy(
	mode: 'balanced',
	maxSteps: 4,
	allowLossy: true,
	allowExternalServices: false
);
```

Fastest example:

```php
new ParserStrategy(
	mode: 'fastest',
	maxSteps: 4,
	speedWeight: 1.2,
	stepPenalty: 0.15
);
```

Best quality example:

```php
new ParserStrategy(
	mode: 'best_quality',
	maxSteps: 5,
	speedWeight: 0.1,
	stepPenalty: 0.04
);
```

Local-only example:

```php
new ParserStrategy(
	mode: 'local_only',
	allowExternalServices: false
);
```

## Planner

The planner builds the best parser chain for a request.

Core responsibilities:

* collect candidate start payloads
* derive target state from requested output
* index routes
* perform uniform-cost search
* apply hard constraints
* score routes
* prevent cycles
* return a deterministic `ParserPlan`

The planner is cycle-safe:

* already visited states are not revisited inside one path
* already used routes are not reused inside one path
* `maxSteps` limits chain length
* a hard node-expansion limit prevents runaway graphs
* stable tie-breaking keeps behavior deterministic

## Scoring

ParseFlow uses additive route costs.

The cost is based on:

* quality profile
* strategy weights
* speed
* monetary cost
* step penalty
* lossy penalty
* external service penalty
* route priority

The lower total cost wins.

Conceptually:

```text
qualityCost = -log(combinedQuality)
speedCost = (1.0 - speed) * speedWeight
moneyCost = monetaryCost * monetaryCostWeight
stepCost = stepPenalty
lossyCost = lossy ? lossyPenalty : 0
externalCost = requiresExternalService ? externalServicePenalty : 0
priorityBonus = priority-based bonus

routeCost = qualityCost + speedCost + moneyCost + stepCost + lossyCost + externalCost - priorityBonus
```

## Execution flow

A full `parse()` call works like this:

```text
Caller
-> IParserService::parse(ParserRequest)
-> normalize strategy
-> resolve source into initial payloads
-> collect parser routes
-> plan best route
-> execute parser steps
-> write final payload to target
-> return ParserResult
```

## Parser result

A parser result contains:

* success flag
* selected plan
* final payload
* target result
* warnings
* metadata

The selected plan is useful for debugging and explaining why a route was chosen.

## Admin explorer

ParseFlow includes an admin display:

```text
ParseFlow\Display\ParserExplorerAdminDisplay
```

Technical display name:

```text
parserexploreradmindisplay
```

The display provides a ModularGrid-based route explorer.

It is intended for:

* inspecting available parser routes
* selecting input/output states
* comparing parser plans
* checking costs and quality dimensions
* opening a row to inspect route details
* copying generated PHP example code

## Explorer UI

The explorer focuses on one selected conversion at a time.

Visible hot filters:

```text
Source
Input
Output
Target
Strategy
```

Optional filters:

```text
External
Lossy
Max steps
```

The explorer intentionally does not list all possible graph paths globally. That would grow too quickly as more parsers are added.

Instead, it shows a bounded result set for one selected input/output conversion.

## Explorer performance safeguards

The explorer has additional performance protection:

* no global full-graph enumeration
* selected input/output pair only
* bounded top result set
* no InfiniteScroll
* row details use already loaded row data
* no MutationObserver-based filter loop
* semantically useless detours are pruned
* direct target routes are treated as terminal for intermediate states

This keeps the UI responsive even when many parser routes are available.

## Example: DOCX to Markdown

Request:

```php
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Target\ReturnParserTarget;

$request = new ParserRequest(
	source: new FileParserSource('/tmp/input.docx'),
	input: new DocumentParserInput('docx'),
	output: new StringParserOutput('markdown'),
	target: new ReturnParserTarget()
);

$result = $parserService->parse($request);
```

Possible plans:

```text
document/docx -> string/markdown
document/docx -> string/html -> string/markdown
```

The explorer avoids irrelevant detours such as:

```text
document/docx -> string/html -> structured/xml -> structured/csv -> string/markdown
```

when a direct `html -> markdown` route already exists.

## Example: HTML string to Markdown

```php
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Source\StringParserSource;
use ParseFlow\Target\ReturnParserTarget;

$request = new ParserRequest(
	source: new StringParserSource($html),
	input: new DocumentParserInput('html'),
	output: new StringParserOutput('markdown'),
	target: new ReturnParserTarget()
);

$result = $parserService->parse($request);
$markdown = $result->payload->value ?? null;
```

## Example: PDF to text

```php
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Target\ReturnParserTarget;

$request = new ParserRequest(
	source: new FileParserSource('/tmp/document.pdf'),
	input: new DocumentParserInput('pdf'),
	output: new StringParserOutput('text'),
	target: new ReturnParserTarget(),
	strategy: new ParserStrategy(
		mode: 'fastest',
		maxSteps: 4
	)
);

$result = $parserService->parse($request);
```

## Example: Structured JSON to file

```php
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Output\StructuredParserOutput;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Target\FileParserTarget;

$request = new ParserRequest(
	source: new FileParserSource('/tmp/document.pdf'),
	input: new DocumentParserInput('pdf'),
	output: new StructuredParserOutput('json'),
	target: new FileParserTarget('/tmp/output.json')
);

$result = $parserService->parse($request);
```

## Creating a parser

A parser should:

1. implement `IParser`
2. return one or more `ParserRoute` objects
3. evaluate route support for the current context
4. execute one step
5. return a `ParserStepResult`

Example skeleton:

```php
<?php declare(strict_types=1);

namespace ParseFlow\Parser\Example;

use ParseFlow\Api\IParser;
use ParseFlow\Dto\ParserPlanningContext;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Dto\ParserStepResult;

class HtmlToMarkdownParser implements IParser {

	public static function getName(): string {
		return 'htmltomarkdownparser';
	}

	public function getRoutes(): array {
		return [
			new ParserRoute(
				parserName: self::getName(),
				routeName: 'html_to_markdown',
				from: new ParserState('string', 'html'),
				to: new ParserState('string', 'markdown'),
				quality: new ParserRouteQuality(
					textQuality: 1.0,
					structureQuality: 0.9,
					layoutQuality: 0.7,
					tableQuality: 0.8,
					speed: 0.95,
					stability: 0.95,
					priority: 100
				)
			)
		];
	}

	public function evaluate(ParserRoute $route, ParserPlanningContext $context): ParserRouteEvaluation {
		return ParserRouteEvaluation::supported($route->quality);
	}

	public function execute(ParserStepRequest $request): ParserStepResult {
		$html = (string) $request->payload->value;

		$markdown = $this->convertHtmlToMarkdown($html);

		return new ParserStepResult(
			new ParserPayload(
				state: $request->route->to,
				value: $markdown,
				metadata: $request->payload->metadata
			)
		);
	}

	private function convertHtmlToMarkdown(string $html): string {
		// Replace this with real conversion logic.
		return trim(strip_tags($html));
	}
}
```

## Parser naming rules

Parser names should be stable and deterministic.

Recommended:

```php
public static function getName(): string {
	return 'htmltomarkdownparser';
}
```

Avoid:

```php
return 'HtmlToMarkdownParser';
return 'html-to-markdown';
return 'my_parser_v2';
return uniqid();
```

The technical name is used for:

* route identity
* class map lookup
* debugging
* admin display
* plan signatures
* generated PHP examples

## Route design guidelines

Good route design is important.

A route should describe one meaningful transformation:

```text
string/html -> string/markdown
document/docx -> string/html
document/pdf -> string/text
structured/json -> string/markdown
```

Avoid creating routes that only exist to game the graph:

```text
string/html -> structured/xml -> structured/csv -> structured/ini -> string/markdown
```

unless each intermediate state is genuinely useful and has realistic quality/cost values.

## Quality profile guidelines

Do not set every quality dimension to `1.0` by default.

Use realistic values.

For example, direct DOCX to Markdown may preserve text well but lose some layout:

```php
new ParserRouteQuality(
	textQuality: 1.0,
	structureQuality: 0.95,
	layoutQuality: 0.75,
	tableQuality: 0.85,
	speed: 0.9,
	stability: 0.95
);
```

An OCR route may be slower and lossy:

```php
new ParserRouteQuality(
	textQuality: 0.75,
	structureQuality: 0.3,
	layoutQuality: 0.2,
	tableQuality: 0.2,
	speed: 0.35,
	stability: 0.8,
	lossy: true
);
```

External services should be marked explicitly:

```php
new ParserRouteQuality(
	textQuality: 0.95,
	structureQuality: 0.95,
	speed: 0.5,
	stability: 0.9,
	monetaryCost: 0.01,
	requiresExternalService: true
);
```

## Avoiding graph explosions

The graph can grow quickly when many parsers exist.

Avoid:

* too many generic wildcard routes
* pass-through routes for every possible state
* routes that convert back and forth between equivalent formats
* inflated quality values on detour routes
* missing `lossy` flags
* missing external service flags
* overly high `maxSteps`

Use:

* realistic route quality
* stable priorities
* conservative wildcard usage
* direct routes for common conversions
* explicit route names
* clear parser responsibilities

## Source resolver

`ParserSourceResolver` converts a source and input declaration into one or more initial payloads.

Examples:

```text
FileParserSource('/tmp/a.pdf') + DocumentParserInput('pdf') -> document/pdf
FileParserSource('/tmp/a.docx') + DocumentParserInput('docx') -> document/docx
StringParserSource($html) + DocumentParserInput('html') -> document/html
StringParserSource($text) + PlainTextParserInput('text') -> string/text
```

Auto-detection can create multiple candidate states.

## Target writer

`ParserTargetWriter` writes the final payload.

Examples:

```text
ReturnParserTarget      -> returns payload in ParserResult
FileParserTarget        -> writes final string/binary/structured result
DirectoryParserTarget   -> writes files or generated artifacts
StreamParserTarget      -> writes final data to stream
```

The target writer is deliberately separate from parser routes.

## Temporary resources

Parser implementations may need temporary files.

Recommended pattern:

* create temporary files through the ParseFlow temp resource manager
* return temporary resource metadata in step results where applicable
* do not leave `/tmp` files unmanaged
* do not assume a temp file survives beyond the parse call
* expose debug information through metadata, not uncontrolled filesystem state

## Error handling

ParseFlow uses dedicated exceptions.

Typical exception classes:

```text
ParserException
ParserExecutionException
ParserPlanningException
ParserTargetException
UnsupportedParserRequestException
ParseFlowRuntimeException
```

Planning failures should throw `UnsupportedParserRequestException`.

Execution failures should be wrapped in a parser execution exception where appropriate.

Target write failures should use target-specific exceptions.

## Capability exploration

The parser service can expose capability data through:

```php
$report = $parserService->explore();
```

The report is useful for:

* admin displays
* parser debugging
* route audits
* documentation generation
* checking which conversions are available

The admin explorer uses this capability data together with bounded route planning.

## Admin integration

To expose the display in a BASE3 administration area, add a display entry with the technical display name:

```text
parserexploreradmindisplay
```

Example conceptual administration entry:

```php
[
	'name' => 'parseflow',
	'label' => 'ParseFlow',
	'displays' => [
		[
			'name' => 'parserexploreradmindisplay',
			'label' => 'Parser Explorer'
		]
	]
]
```

Language keys can be added to administration language files:

```ini
base3_admin_tab_parseflow = "ParseFlow"
base3_admin_subtab_parserexploreradmindisplay = "Parser Explorer"
```

German example:

```ini
base3_admin_tab_parseflow = "ParseFlow"
base3_admin_subtab_parserexploreradmindisplay = "Parser-Explorer"
```

## ModularGrid dependency

The admin explorer uses ModularGrid assets from ClientStack.

Expected asset paths:

```text
plugin/ClientStack/assets/modulargrid/styles/modulargrid.css
plugin/ClientStack/assets/modulargrid/index.js
```

The display resolves assets through `IAssetResolver`.

## Smoke test

The package includes a smoke test:

```bash
php smoke.php
```

Expected output shape:

```text
ok parserCount=91 routeCount=91
```

Exact counts depend on the included parser pack.

## Linting

Run PHP syntax checks:

```bash
find src tpl -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Recommended manual checks

After installation:

1. regenerate or refresh the BASE3 class map if needed
2. open the ParseFlow admin explorer
3. verify that parser count and route count are shown
4. select a common input/output pair
5. open a row detail
6. copy the generated PHP example
7. run a simple `ParserRequest` against `IParserService`
8. test at least one direct and one multi-step route

## Development workflow

Recommended flow for adding a parser:

1. create parser class under `src/Parser/...`
2. implement `IParser`
3. define one or more `ParserRoute` instances
4. provide realistic `ParserRouteQuality`
5. implement `evaluate()`
6. implement `execute()`
7. run `php -l`
8. run `smoke.php`
9. inspect route in admin explorer
10. test through `IParserService::parse()`

## Coding style

Use the BASE3 style:

* PHP strict types
* English code comments
* tab indentation
* opening braces on the same line
* class name equals filename
* namespace mirrors directory structure
* no anonymous throwaway architecture
* no hidden magic
* deterministic names
* explicit DTOs
* explicit route definitions

Example:

```php
class ExampleParser implements IParser {

	public static function getName(): string {
		return 'exampleparser';
	}
}
```

## Security notes

Be careful with parsers that:

* call external services
* execute CLI tools
* read arbitrary file paths
* process uploaded archives
* parse XML
* fetch URLs
* write files
* create temporary resources

Recommended safeguards:

* mark external routes with `requiresExternalService`
* keep external services disabled by default
* validate file paths
* avoid URL sources without an SSRF concept
* limit file sizes
* disable unsafe XML features
* use timeouts for CLI tools
* clean up temporary files
* never trust parser input metadata blindly

## Performance notes

Parser graphs can become large.

Performance depends on:

* number of parser routes
* number of compatible intermediate states
* `maxSteps`
* route quality values
* wildcard route usage
* selected strategy

The core planner uses graph search and cycle protection.

The admin explorer additionally uses a bounded, selected-conversion approach and does not enumerate the full graph globally.

## Current explorer behavior

The explorer intentionally shows a bounded result set for one selected conversion.

This is by design.

It avoids expensive global listings like:

```text
all inputs -> all outputs -> all possible chains
```

Instead, it answers:

```text
Given input X and output Y, what are the best available parser plans?
```

## When to add a direct parser route

Add a direct route when:

* the conversion is common
* the conversion is semantically meaningful
* it avoids unnecessary intermediate states
* it is stable enough to expose as a first-class capability

Example:

```text
string/html -> string/markdown
```

is better than forcing:

```text
string/html -> structured/xml -> structured/csv -> string/markdown
```

## When to add a multi-step route

Do not manually define a multi-step route as one parser route unless it is truly atomic.

Prefer separate parser routes:

```text
document/docx -> string/html
string/html -> string/markdown
```

The planner can combine them automatically.

## Troubleshooting

### The parser service finds no routes

Check:

* parser classes implement `IParser`
* parser classes are under `src/`
* class map was regenerated
* `getName()` returns a stable lowercase name
* parser constructors can be instantiated by the class map
* `getRoutes()` returns at least one `ParserRoute`

### The admin explorer is empty

Check:

* ModularGrid assets are available
* ClientStack assets are installed
* `IAssetResolver` resolves ModularGrid CSS and JS
* browser console has no module import errors
* `parserexploreradmindisplay` JSON endpoint is reachable
* parser routes exist

### A route is not selected

Check:

* route `from` state matches the resolved input state
* route `to` state can reach the requested output state
* `allowExternalServices` is compatible
* `allowLossy` is compatible
* parser is not disabled in strategy
* `maxSteps` is high enough
* route quality and priority do not make the route too expensive

### The planner selects strange detours

Check:

* quality values are realistic
* detour routes are not all marked as perfect
* direct routes have suitable priority
* wildcard routes are not too broad
* `maxSteps` is not too high
* external/lossy flags are set correctly

### Execution fails after planning

Check:

* `evaluate()` and `execute()` agree on route support
* parser receives the expected payload value type
* temporary files still exist during execution
* target writer supports the final payload type
* parser step returns a valid `ParserStepResult`

## Repository setup

Suggested repository metadata:

Description:

```text
Graph-based parser service for BASE3, discovering modular parser routes and planning efficient Source/Input → Output/Target conversions with scoring, strategies, execution, and an admin explorer.
```

Suggested topics:

```text
base3
parseflow
parser
parser-service
document-processing
graph
workflow
php
modular-parser
conversion
```

Suggested license:

```text
GPL-3.0
```

## Git ignore

Suggested `.gitignore`:

```gitignore
.DS_Store
Thumbs.db
.idea/
.vscode/
*.log
*.tmp
*.cache
/tmp/
/var/
/vendor/
node_modules/
```

Do not ignore source files, templates, language files or parser definitions.

## Versioning

Recommended versioning:

```text
0.1.0 initial parser service and parser pack
0.2.0 admin explorer and capability report
0.3.0 external parser integrations
1.0.0 stable public parser API
```

## Compatibility

ParseFlow expects:

* PHP with strict type support
* BASE3 framework interfaces
* BASE3 class map
* BASE3 dependency container
* ClientStack ModularGrid assets for the admin explorer

## License

GPL-3.0.

See `LICENSE`.

## Author

Developed by Daniel Dahme.

## Project URLs

```text
https://base3.de/v/parseflow
https://github.com/ddbase3/ParseFlow
```

## Summary

ParseFlow turns parser capabilities into a graph.

Parsers declare what they can transform. The service plans the best route. The executor runs the selected parser chain. The target writer returns or stores the result. The admin explorer makes the graph inspectable.

This keeps parsing extensible, testable and explainable while following BASE3 conventions.

