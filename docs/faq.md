# ParseFlow Frequently Asked Questions

This FAQ documents the ParseFlow component itself. It describes the parser service, parser graph, bundled parser capabilities, execution model, administration interfaces, file handling, and extension points present in the current package.

For the compact list of bundled parser families and counts, see [ParserCatalog.md](ParserCatalog.md).

## What is ParseFlow?

ParseFlow is a graph-based parsing and transformation service for BASE3. It models conversions as directed parser routes and can automatically plan a chain from an input representation to a requested output representation.

A request separates four concerns:

```text
Source + Input -> parser graph -> Output + Target
```

The source describes where the original data comes from. The input describes how it should be interpreted. The output describes the requested semantic representation. The target describes where the final payload should be delivered.

## What is the central API?

The primary service contract is:

```text
ParseFlow\Api\IParserService
```

The default implementation is:

```text
ParseFlow\Service\DefaultParserService
```

It exposes these main operations:

```php
parse(ParserRequest $request): ParserResult
plan(ParserRequest $request): ParserPlan
supports(ParserRequest $request): bool
listRoutes(): array
explore(?ParserExploreRequest $request = null): ParserCapabilityReport
```

`parse()` plans and executes a conversion. `plan()` returns the selected route without executing it. `supports()` checks whether a route can currently be planned. `listRoutes()` exposes discovered graph edges. `explore()` returns parser capability metadata.

## How are parsers discovered?

Parsers implement:

```text
ParseFlow\Api\IParser
```

They are discovered through the BASE3 `IClassMap`. ParseFlow does not maintain a hard-coded central parser registry.

Each parser has a stable technical name through `getName()`. The route registry uses those names to identify parser implementations and graph edges.

## What does one parser define?

An `IParser` has three responsibilities:

1. declare one or more `ParserRoute` objects
2. evaluate whether a route is supported for the current planning context
3. execute a selected parser step

This keeps capability declaration, planning and execution separate.

## What is a parser route?

A parser route is one directed edge between two semantic `ParserState` values.

Example:

```text
document/docx -> string/markdown
```

A route contains the parser name, route name, source state, target state, quality profile, features and requirements.

## What is a parser state?

A `ParserState` describes the semantic representation of a payload. Common examples are:

```text
document/docx
string/text
string/markdown
structured/json
image/png
```

A state is not the same thing as a source or target. For example, a local file is a transport source while `document/docx` is the graph state of the content carried by that source.

## Which source types are supported?

The package contains these source classes:

```text
FileParserSource
StringParserSource
BinaryParserSource
StreamParserSource
StructuredParserSource
```

A file source carries a path. A string source carries a PHP string. A binary source carries a binary string. A stream source carries a PHP stream resource. A structured source carries an already structured PHP value.

## Which input types are supported?

The package contains:

```text
AutoDetectParserInput
DocumentParserInput
ImageParserInput
PlainTextParserInput
StructuredParserInput
```

The input type controls the initial semantic interpretation of the source.

## How does auto-detection work?

The current source resolver mainly uses the declared input type and file extension. For file sources it also detects MIME type through `finfo` when available, but MIME detection is stored as state metadata and is not a full content-validation boundary.

For string sources, type detection is based on the requested format, such as HTML, JSON or XML.

Callers should therefore not treat `AutoDetectParserInput` as malware scanning, upload validation or authoritative file-type verification.

## Which output types are supported by the API?

ParseFlow provides output descriptors for:

```text
StringParserOutput
StructuredParserOutput
FilesParserOutput
ChunksParserOutput
BinaryParserOutput
StreamParserOutput
ImageParserOutput
```

Whether a specific requested output can actually be produced depends on the parsers discovered in the active runtime.

## Which target types are supported?

The built-in target writer supports:

```text
ReturnParserTarget
FileParserTarget
DirectoryParserTarget
StreamParserTarget
```

A return target returns the final `ParserPayload`. A file target writes one result to a path. A directory target writes an array of artifacts to a directory. A stream target writes the serialized result to a PHP stream resource.

## Does ParseFlow write files automatically?

Only when the caller explicitly selects a file or directory target, or when an extension parser creates temporary resources during execution.

A normal `ReturnParserTarget` does not persist the final result.

## How are directory target filenames handled?

For `DirectoryParserTarget`, artifact names are reduced to `basename()` before being appended to the selected target directory. This prevents an artifact key such as `../../file.txt` from directly escaping the configured output directory.

The target directory itself is supplied by the caller and is not sandboxed by ParseFlow.

## Are file source and target paths restricted to a ParseFlow directory?

No. `FileParserSource`, `FileParserTarget` and `DirectoryParserTarget` accept caller-provided filesystem paths.

ParseFlow checks that a source file exists and is readable, and it creates target directories when necessary. It does not implement a component-specific allowlist of readable or writable filesystem locations.

Filesystem authorization therefore belongs at the caller and deployment boundary.

## How does planning work?

`ParserPlanner` builds a route index and performs a cost-based graph search from the initial state to the requested output state.

The current planner includes:

- route and state cycle guards
- maximum step limits
- a hard limit of 5,000 node expansions
- deterministic tie-breaking
- route support evaluation before selection
- parser allowlists and denylists through `ParserStrategy`

The cheapest acceptable route wins.

## What happens when the planner reaches its graph expansion limit?

Planning stops and adds a warning:

```text
Parser planning stopped because the maximum number of graph expansions was reached.
```

If no valid route was found, the request fails with `UnsupportedParserRequestException`.

## How are routes scored?

Routes expose a `ParserRouteQuality` profile. The score can include:

- text quality
- structure quality
- layout quality
- table quality
- image quality
- semantic quality
- speed
- stability
- monetary cost
- lossy status
- external-service status
- priority

`ParserScoreCalculator` converts these values into additive route costs. Lower cost is preferred.

## Which strategies exist?

The DTO supports modes including:

```text
balanced
fastest
best_quality
best_text
best_structure
local_only
```

The effective behavior is controlled by the weights and constraints in `ParserStrategy`.

The strategy can also define maximum steps, whether lossy routes are allowed, whether external routes are allowed, required route features, an allowed parser list and a disabled parser list.

## Are external parser services enabled by default?

No. `ParserStrategy` defaults to:

```text
allowExternalServices = false
```

Routes marked with `requiresExternalService = true` are therefore rejected unless a caller explicitly enables them.

The bundled parser catalog in this package is local PHP processing. The current core package does not include Docling, Pandoc, LibreOffice, OCR services or PDF extraction services.

## Does this package make outbound network requests?

The bundled parser implementations do not contain a URL source or a built-in HTTP client. The parser catalog operates locally on values, streams and filesystem content supplied by the caller.

Future or separately installed parser implementations can extend `IParser` and may introduce external-service routes. Such routes should declare `requiresExternalService = true` and remain subject to the caller's strategy.

## Does ParseFlow execute shell commands?

The bundled parsers do not execute CLI tools through `exec`, `shell_exec`, `proc_open` or similar mechanisms.

The current Office parsers operate directly on OpenXML ZIP/XML structures and do not call LibreOffice.

## Does ParseFlow support PDF parsing?

The current core parser catalog does not include a PDF parser. A PDF input may be representable as a graph state, but a successful conversion requires an installed parser that provides a route from that state.

The parser catalog explicitly states that PDF, OCR, LibreOffice, Docling and Pandoc are not part of the core package.

## Which parser families are bundled?

The current parser catalog contains local parsers in these families:

- Core
- HTML
- Markdown
- CSV
- JSON
- XML and XML-family formats
- INI and ENV
- Office OpenXML
- Image

The current `ParserCatalog.md` lists 91 parsers in total. Exact active counts can still vary with runtime availability, such as missing PHP extensions.

## What does the HTML parser family do?

The bundled HTML parsers can derive representations such as text, Markdown, DOM-like JSON, XML, tables, headings, links, images, metadata, forms, JSON-LD, article text, lists and code-block files.

These transformations operate on supplied HTML. They do not fetch linked pages, images or form targets.

## Does the HTML parser preserve form values?

`HtmlFormsToJsonParser` extracts form metadata including input `name`, `type` and `value` attributes. If the supplied HTML contains personal or sensitive values inside those attributes, they can appear in the JSON output.

## What does the Markdown parser family do?

The bundled Markdown parsers can produce text, HTML, a simple JSON AST, headings, links, images, front matter, table representations and extracted fenced code blocks.

The HTML conversion escapes the Markdown line text before placing it in generated HTML elements.

## What does the CSV parser family do?

CSV content can be transformed to JSON, XML, HTML tables, Markdown tables, NDJSON and INI-like output. The implementation uses PHP CSV functions and `php://temp` for in-memory-style processing.

## What does the JSON parser family do?

JSON can be transformed to XML, CSV, HTML tables, Markdown tables, NDJSON, a YAML-like text form and a PHP array source representation.

The PHP array route produces PHP source text. ParseFlow does not execute that generated PHP text.

## What does the XML parser family do?

The package includes general XML conversions and format-specific helpers for RSS, Atom, sitemap XML, SVG, GPX, KML and GeoJSON-related conversions.

XML processing uses PHP DOM and SimpleXML when available.

## Does ParseFlow explicitly disable network access inside XML parsing?

The current XML helper does not pass `LIBXML_NONET` to every XML parse operation. It uses internal libxml error handling but does not provide a component-level XML security policy.

Deployments that accept untrusted XML should verify the behavior of the deployed PHP/libxml version and enforce appropriate input controls at the boundary where untrusted content enters the system.

## Can SVG output contain active content?

`SvgToHtmlPreviewParser` wraps the supplied SVG value directly inside an HTML `<div>`. It does not sanitize the SVG payload.

That output must therefore be treated as untrusted HTML if it originated from an untrusted source. A consumer must not render it into a browser without an appropriate sanitization and content-security boundary.

## What does the Office parser family support?

The current Office parsers support OpenXML formats through `ZipArchive` and DOM processing. They include DOCX, XLSX and PPTX transformations such as text extraction, simple HTML or Markdown representations, metadata extraction, image extraction and spreadsheet table extraction.

They do not invoke a desktop office suite.

## Can Office metadata contain personal data?

Yes. OpenXML core properties can contain fields such as creator, last modifier, timestamps and other document metadata. ParseFlow can expose those values through metadata parser routes.

The exact fields depend on the source file.

## Can Office documents expose embedded images?

Yes. The DOCX and PPTX image parsers can extract files from the corresponding OpenXML media directories.

The returned artifact map uses `basename()` for extracted archive entry names. A directory target also applies `basename()` again before writing each artifact.

## Does ParseFlow impose archive or decompression limits?

The current Office OpenXML helper does not implement a component-specific limit for ZIP entry count, uncompressed size or aggregate extracted data size.

Callers processing untrusted or very large archives should enforce file-size, resource and execution limits before invoking ParseFlow.

## What does the image parser family support?

The bundled image parsers include PNG, JPEG and WebP conversions, thumbnail generation, basic image metadata extraction and JPEG EXIF extraction.

GD is used for image decoding and conversion when available. EXIF extraction depends on the PHP EXIF extension.

## Can EXIF output contain sensitive information?

Yes. EXIF can contain timestamps, device information, software information, comments and, depending on the image, geolocation-related metadata.

`JpegExifToJsonParser` returns the EXIF data made available by PHP. Callers should treat that output as potentially sensitive.

## Are image dimensions limited?

The current image helper does not impose a component-specific pixel-count or image-memory limit before decoding through GD.

Resource limits for untrusted or very large images belong at the caller and deployment boundary.

## How are parser availability requirements handled?

A parser can return unsupported during `evaluate()` when required PHP functionality is unavailable.

Bundled parsers check for features such as:

- `DOMDocument`
- SimpleXML
- `ZipArchive`
- GD image functions
- EXIF functions

A route can therefore exist in the package but be unavailable in a particular PHP runtime.

## What happens during execution?

`ParserPlanExecutor` selects the initial payload matching the chosen start state, then executes each planned step in order.

Each parser receives a `ParserStepRequest` containing the original request, the planned step, current payload and request options. The returned payload becomes the input to the next step.

## Are intermediate payloads persisted?

Not by the core execution pipeline. They remain in PHP memory or in resources referenced by the parser implementation unless a parser or selected target explicitly writes something.

## How are temporary resources handled?

`ParserPlanExecutor` collects temporary resource paths returned by parser steps and passes them to `ParserTempResourceManager`.

At the end of execution the manager deletes registered files unless this option is set:

```php
['keepTemporaryResources' => true]
```

The cleanup routine deletes files only. Extension parsers that create directories or other resource types need to manage those resources appropriately.

The bundled parser set in this package does not currently return temporary-resource paths through `ParserStepResult`.

## Where are temporary files created by the helper manager?

`ParserTempResourceManager::createTempFile()` uses:

```text
sys_get_temp_dir()
```

with a `parseflow_` style prefix by default.

## Does ParseFlow have its own database tables?

No. The core parser runtime does not define its own database schema or migration provider.

## Does ParseFlow persist parser configuration?

The parser administration display can persist configuration for discovered parsers that implement BASE3 `ISchemaProvider`.

Those records are stored through `ISettingsStore` under the group:

```text
parseflow-parser
```

The actual storage backend depends on the active BASE3 `ISettingsStore` implementation.

## Are the bundled parsers configurable through that settings UI?

In the current package, the bundled parser implementations do not implement `ISchemaProvider`. The configuration mechanism exists for parser implementations that expose a schema, including extensions discovered through the class map.

## Does the parser administration UI mask secret fields?

The current `ParserAdminDisplay` validates configuration by schema type, range and enum values, but it does not implement a dedicated secret-value masking mechanism.

Stored configuration is returned in the detail response as both `config` and `effectiveConfig` and can be rendered in the browser. Parser extensions should therefore not place raw credentials in this configuration path unless a separate secret-resolution mechanism is used at the responsible architecture boundary.

## What administration views are included?

The current source contains:

```text
ParseFlow\Display\ParserAdminDisplay
ParseFlow\Display\ParserExplorerAdminDisplay
ParseFlow\Output\ParseFlowGraphOutput
```

`ParserAdminDisplay` lists discovered parsers and can manage schema-based parser settings. `ParserExplorerAdminDisplay` explores possible conversion plans. `ParseFlowGraphOutput` renders discovered parser states and routes as HTML or JSON.

## Does the parser explorer parse user documents?

No. The explorer works from route metadata and synthetic state combinations. It calculates possible plans and generates example PHP code. It does not upload or parse a real document as part of its normal explorer workflow.

## What information does the graph output expose?

The graph output exposes parser names, route names, semantic states, selected quality metrics and parser-count metadata. It does not include document content from previous parse requests because ParseFlow does not keep a global parse history.

## Does ParseFlow implement its own admin authorization checks?

The ParseFlow displays and graph output do not contain a component-specific user or permission check. Access depends on how the host application exposes and protects those outputs.

This is particularly important for `ParserAdminDisplay`, because its JSON endpoint can save and reset parser settings.

## Does ParseFlow implement its own CSRF token for parser settings changes?

The current `ParserAdminDisplay` accepts JSON POST requests for `save` and `reset` actions but does not implement a ParseFlow-specific CSRF token.

A deployment that exposes this display in a browser administration area should provide the appropriate request-protection boundary through the host application.

## Does the UI store anything in the browser?

Both ModularGrid-based admin views configure browser `sessionStorage` for grid state such as query text, filters and column state.

Current keys include:

```text
parseflow-parser-admin-grid-v3
parseflow-parser-explorer-grid-v5
```

The parser configuration values themselves are persisted through the server-side `ISettingsStore`, not through these session-storage entries.

## Does the explorer use the clipboard?

Yes. The explorer can generate an example PHP request and copy it to the browser clipboard when the user activates the copy action.

The generated example uses placeholder values such as `/tmp/input.docx`, `/tmp/output.md` or `$inputText = '...'`. It does not contain content from an uploaded user document because the explorer does not process one.

## Does ParseFlow log parser content?

The core ParseFlow runtime does not inject or call `ILogger` and does not maintain its own parser-content audit log.

A consuming application or an extension parser may log requests, results or exceptions outside ParseFlow. That behavior is outside this component and must be assessed where it is implemented.

## Are parse results cached?

The current core parser service does not implement a result cache.

## Does ParseFlow keep a history of parse requests?

No. The core component has no parse-history repository and no database table for previous parser requests or results.

## Can parser errors reveal file paths?

Yes. For example, an unreadable file source produces an exception message containing the supplied source path. File-source metadata also carries the path through the parser payload.

Consumers should avoid exposing raw parser exceptions or payload metadata to untrusted users when filesystem paths are considered sensitive infrastructure information.

## Does a returned payload preserve source metadata?

Yes. Parser steps normally preserve the payload metadata. A `FileParserSource` contributes its `path` to source metadata, so a result returned as a `ParserPayload` can still carry the original path metadata.

## Are output formats automatically safe to render in a browser?

No. ParseFlow transforms data. It is not a universal HTML sanitizer.

Some outputs are escaped by their specific parser implementation, while others intentionally preserve source markup or URLs. Consumers must apply the correct escaping, sanitization and content-security rules for the context in which a result is used.

## Can parser extensions add new routes without modifying ParseFlow?

Yes. A discoverable class implementing `IParser` can contribute routes through the BASE3 class map. The central parser service automatically includes those routes.

This is the intended extension model.

## Should extension parsers mark external-service usage?

Yes. If a parser sends content to an external service, its route quality should set:

```php
requiresExternalService: true
```

This allows the planning strategy to exclude it when external processing is not allowed.

External parsers should also accurately represent monetary cost, lossiness, quality and runtime requirements.

## Can a caller force one parser?

`ParserRequest` can specify a parser name. The planner then ignores routes belonging to other parsers for that request.

A strategy can also provide `allowedParsers` or `disabledParsers`.

## Is planning deterministic?

The planner uses stable route keys and deterministic tie-breaking when costs and step counts are equivalent. This is intended to keep the selected plan explainable and repeatable for the same active route set and strategy.

## What happens if no route exists?

Planning throws `UnsupportedParserRequestException` with a message indicating that no parser plan was found for the requested source/output state.

`supports()` catches that exception and returns `false`.

## What should be checked before enabling ParseFlow for untrusted uploads?

At minimum, the responsible integration should define:

- maximum accepted file size
- archive and decompression limits
- image resource limits
- allowed source paths and target paths
- accepted content types and extensions
- XML security behavior for the deployed PHP/libxml version
- HTML and SVG sanitization for browser rendering
- timeout and memory limits
- authorization for administration views
- CSRF protection for parser-setting changes
- retention rules for explicitly written outputs and retained temporary files

These controls belong at the boundary where untrusted content is accepted or output is published.

## Where should I look for the exact bundled parser inventory?

Use [ParserCatalog.md](ParserCatalog.md). The source tree under `src/Parser/` is authoritative for the parser classes actually shipped in the package.
