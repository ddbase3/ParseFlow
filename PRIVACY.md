# ParseFlow Privacy and Data Processing

This document describes data processing performed by the ParseFlow component itself. It is a technical privacy and data-flow reference, not a legal privacy notice.

ParseFlow is a generic parser and transformation component. The data it processes depends entirely on the content supplied by its callers. That content can contain ordinary application data, personal data, confidential information, credentials, document metadata, location information or other sensitive material.

## 1. Component scope

ParseFlow provides:

- source abstractions for files, strings, binary values, streams and structured values
- semantic input and output descriptors
- graph-based parser route discovery and planning
- local parser implementations for common structured, document and image formats
- parser execution
- result delivery to return values, files, directories or streams
- capability and graph exploration
- parser administration for schema-configurable parser extensions

The component does not provide user identity, authentication, general authorization, upload management, malware scanning, long-term document storage or a legal retention policy.

## 2. Main processing model

A parse request follows this sequence:

```text
caller
-> ParserRequest
-> ParserSourceResolver
-> ParserPlanner
-> ParserPlanExecutor
-> parser implementations
-> ParserTargetWriter
-> ParserResult
```

The content remains inside this execution path unless the selected target, an extension parser or the consuming application deliberately moves it elsewhere.

## 3. Data categories ParseFlow can process

Depending on the caller, ParseFlow can process any data contained in the supplied source, including:

- free text
- HTML and Markdown
- JSON, CSV, XML, INI and ENV-style data
- office document content
- spreadsheet cells
- presentation text
- embedded images
- image binary data
- document and image metadata
- form fields embedded in HTML
- links and URLs contained in documents
- geographic coordinates in GPX, KML or GeoJSON
- code blocks
- arbitrary structured PHP arrays

ParseFlow does not determine whether these values are personal data. The responsible application must classify the data according to its actual use case.

## 4. Personal data can be present in ordinary parser input

Typical examples include:

- names and contact information in documents
- account identifiers in CSV or JSON
- form field values inside HTML
- authors and last modifiers in Office metadata
- timestamps and device metadata in EXIF
- GPS or other coordinates in EXIF, GPX, KML or GeoJSON
- comments, titles and descriptions
- URLs containing identifiers or query parameters
- credentials accidentally embedded in configuration-like text

Parser transformations can preserve, reveal, reorganize or duplicate those values in another representation.

## 5. ParseFlow does not create its own document database

The core runtime has no dedicated database schema for parser requests, source content, parsed results or parser history.

A normal in-memory parse request is therefore not automatically retained by ParseFlow after the PHP execution context ends.

## 6. ParseFlow does not keep a parse history

There is no core repository for historical parser requests, selected plans or results.

The administration explorer works from parser capability metadata rather than previous document-processing activity.

## 7. Parser configuration can be persisted through ISettingsStore

`ParserAdminDisplay` supports configuration for parsers implementing BASE3 `ISchemaProvider`.

The settings group is:

```text
parseflow-parser
```

The storage location and retention behavior depend on the configured BASE3 `ISettingsStore` backend.

The bundled parsers in the current package do not implement `ISchemaProvider`, so this path is primarily an extension point for configurable parser implementations.

## 8. Parser configuration is not a secret store

The current parser administration code has no dedicated secret-masking or late secret-resolution feature.

For a configurable parser, detail responses include the stored configuration in:

```text
config
effectiveConfig
```

These values can be rendered in the browser administration UI.

Raw passwords, API tokens and similar credentials should therefore not be stored directly in this settings path unless the responsible parser and host application introduce an appropriate secret boundary.

## 9. Source file processing

`FileParserSource` carries a caller-supplied filesystem path.

Before planning, `ParserSourceResolver` verifies that the path points to a readable file. It can inspect extension, file size and MIME type. The initial parser payload carries the path as its value for file-based formats that read from disk.

ParseFlow does not copy every source file to private component storage before parsing.

## 10. File paths are potentially sensitive metadata

`FileParserSource::getMetadata()` includes:

```text
path
```

Parser steps normally preserve payload metadata. This means a returned payload can carry a local filesystem path even after content has been transformed.

Unreadable-source errors also include the supplied path.

Applications that expose parser errors or result metadata to end users should decide whether local paths need to be removed or redacted at that application boundary.

## 11. File source access is caller-controlled

ParseFlow does not restrict `FileParserSource` to a specific upload or data directory. Any readable path supplied by an authorized caller can be used.

The caller is therefore responsible for deciding which paths may be supplied to ParseFlow.

This is a filesystem access boundary, not a parsing fallback problem.

## 12. String, binary and structured sources

`StringParserSource`, `BinaryParserSource` and `StructuredParserSource` keep their supplied values in PHP memory during processing.

The values are passed through `ParserPayload` objects and parser steps until a final target is reached.

Large values can therefore have a direct memory impact.

## 13. Stream sources

`StreamParserSource` carries a PHP stream resource. Parsers that use generic conversion helpers may read from the stream through `stream_get_contents()`.

The source stream lifecycle is owned by the caller. ParseFlow does not automatically close caller-owned streams.

## 14. Result targets determine persistence

The selected target determines whether a result is merely returned or written somewhere.

### Return target

`ReturnParserTarget` returns the final `ParserPayload` and does not persist it.

### File target

`FileParserTarget` serializes the final value and writes it to the caller-supplied path.

### Directory target

`DirectoryParserTarget` writes an array of artifacts into the caller-supplied directory.

### Stream target

`StreamParserTarget` writes the final serialized value into a caller-supplied stream.

## 15. File and directory targets are not sandboxed

The target writer does not enforce a ParseFlow-specific allowed root directory.

For a file target, it creates the parent directory when required and writes to the selected path. For a directory target, it creates the selected directory and writes artifacts below it.

The responsible application must decide which filesystem destinations are permitted.

## 16. Directory artifact names are reduced to basenames

When writing a directory target, ParseFlow uses `basename()` for each artifact key before constructing the output path.

This prevents an artifact filename itself from directly supplying parent-directory traversal components.

This does not restrict the root directory selected by the caller.

## 17. Structured output can create additional copies of personal data

Transformations such as JSON to CSV, HTML to JSON, DOCX to text or XML to Markdown can create a new representation of the same source information.

If that representation is written to a file or another persistent target, it becomes an additional data copy with its own retention and access-control requirements.

ParseFlow does not automatically link the lifecycle of such an output file to the lifecycle of the original source.

## 18. Temporary resources

`ParserPlanExecutor` can collect temporary file paths from `ParserStepResult::temporaryResources`.

Registered files are removed in a `finally` block after execution unless the request contains:

```php
['keepTemporaryResources' => true]
```

This provides cleanup for files reported through the parser-step contract.

## 19. Temporary resource limits

`ParserTempResourceManager` deletes registered files with `unlink()`. It does not recursively remove directories or arbitrary non-file resources.

An extension parser that creates directories, sockets, remote objects or resources not represented by a local file path must clean up those resources at its own implementation boundary.

## 20. Temporary files can be intentionally retained

When `keepTemporaryResources` is enabled, registered temporary files are not deleted by the executor.

The caller is then responsible for retention, protection and deletion of those files.

The bundled parser implementations in the current package do not currently return temporary resource paths through `ParserStepResult`.

## 21. Local temporary file location

When `ParserTempResourceManager::createTempFile()` is used, it delegates to:

```text
sys_get_temp_dir()
```

The actual path and operating-system permissions therefore depend on the PHP host environment.

## 22. No built-in outbound network processing in the bundled parser set

The bundled parsers do not contain a URL-fetch source, HTTP client or network transport. They transform content already supplied by the caller.

The current core package therefore does not itself send document content to an external parsing provider.

## 23. External parser extensions are possible

Parser implementations discovered through the BASE3 class map can add new routes.

A route that calls an external service should declare:

```text
requiresExternalService = true
```

The default parser strategy rejects such routes because `allowExternalServices` defaults to `false`.

When an installation adds an external parser implementation, its provider, region, request content, credentials, logs, retention and transfer rules must be documented for that implementation.

## 24. No built-in CLI execution in the bundled parser set

The current parser code does not execute LibreOffice, Pandoc, OCR engines or other command-line tools.

This reduces the core package's execution boundary to PHP and its enabled extensions.

## 25. Office OpenXML processing

DOCX, XLSX and PPTX processing uses `ZipArchive` and DOM/XML parsing.

Relevant data can include:

- document text
- spreadsheet cell values
- presentation text
- embedded image binaries
- core document properties

The package reads selected ZIP entries directly from the source file.

## 26. Office document metadata can identify people

Core OpenXML properties can expose metadata such as creator, last modifier and document timestamps.

The metadata parser routes intentionally return those values. Consumers should therefore treat metadata extraction as potentially personal-data processing even when the visible document body appears non-personal.

## 27. Office image extraction creates copies of embedded media

DOCX and PPTX image routes return the contents of media entries from the OpenXML archive.

If the caller uses a directory target, those binaries are copied into the selected directory.

The resulting files are independent data copies and are not automatically deleted when the original document is removed.

## 28. Archive resource limits are not enforced by ParseFlow

The current Office helper does not define a maximum archive size, maximum uncompressed byte count, maximum file count or decompression ratio.

For untrusted input, file-size and resource constraints should be enforced before ParseFlow processes the document.

## 29. Image processing

The image parser family can decode and convert image content through GD when available.

Image values can be read either from an existing file path or from an in-memory binary string.

The current helper does not define a component-specific pixel-count or memory limit before decoding.

## 30. EXIF processing

`JpegExifToJsonParser` uses PHP `exif_read_data()` for local JPEG file paths.

EXIF can contain data such as:

- capture timestamps
- camera/device information
- software metadata
- comments
- orientation and technical properties
- geolocation-related fields when present in the source image

The returned JSON should therefore be treated as potentially sensitive metadata.

## 31. HTML processing

HTML parser routes use `DOMDocument` for structural extraction.

Possible extracted values include:

- visible text
- element attributes
- links
- image sources
- metadata
- headings
- table cells
- form actions and input values
- JSON-LD script content

These outputs can surface values that were not visually prominent in the original page.

## 32. HTML form extraction can expose hidden values

`HtmlFormsToJsonParser` includes input `value` attributes in its result.

A hidden field, prefilled identifier, email address or other embedded value can therefore become explicit structured output.

Callers should not assume that extracting form structure is metadata-only processing.

## 33. HTML links and image references are not fetched

HTML link and image parsers extract `href` and `src` strings. They do not request those URLs.

A URL can still contain personal identifiers, signed tokens or query parameters, so the extracted string itself can be sensitive even without a network request.

## 34. JSON-LD extraction

`HtmlJsonLdExtractorParser` returns decoded JSON-LD values where possible and raw JSON-LD text otherwise.

JSON-LD may include names, organizations, addresses, identifiers, URLs or other personal and business data from the supplied HTML.

## 35. XML processing

General XML and XML-family parsers use SimpleXML and `DOMDocument`.

The current XML helper enables internal libxml error handling but does not consistently apply an explicit `LIBXML_NONET` flag to all parse operations.

For untrusted XML, the responsible deployment should verify the behavior of the deployed PHP/libxml version and establish XML input controls before ParseFlow is invoked.

## 36. SVG processing and browser rendering

`SvgToHtmlPreviewParser` returns:

```html
<div class="svg-preview">...original SVG value...</div>
```

The SVG payload is not sanitized by that parser.

If a consumer inserts this output into a browser as trusted HTML, source-controlled active SVG features could become executable browser content. ParseFlow does not provide the sanitization boundary for that use case.

## 37. Generated HTML is not uniformly equivalent to sanitized HTML

Some bundled parsers escape inserted text, for example Markdown-to-HTML and CSV-to-HTML conversions. Other routes intentionally preserve or embed source markup.

Consumers must evaluate the exact parser route before treating a generated HTML string as safe for direct rendering.

## 38. JSON-to-PHP output is source text only

`JsonToPhpArrayParser` produces text beginning with:

```php
<?php return ...;
```

ParseFlow does not execute that output.

If a caller writes it into an executable PHP location and later includes it, that is a separate action by the caller and should be governed accordingly.

## 39. Geographic data processing

GPX, KML and GeoJSON transformations can process latitude, longitude, names and other feature properties.

Location data can be personal data when it relates to a person, device or identifiable activity. Format conversion does not reduce that sensitivity by itself.

## 40. Parser output metadata

`ParserPayload` carries an arbitrary metadata array through the parser graph. Bundled parsers generally preserve that metadata rather than stripping it.

Callers and extension parsers can therefore attach additional sensitive values that survive multiple parser steps.

## 41. Parser plans do not normally contain document bodies

A `ParserPlan` describes selected routes, states, costs, warnings and planning metadata. It is intended to explain the selected conversion path.

Document content is carried separately in `ParserPayload`.

This separation is useful when exposing planning diagnostics, but extension parsers can still place arbitrary information in warnings or metadata, so diagnostics should be treated as implementation-provided data.

## 42. Capability exploration is metadata processing

`IParserService::explore()` reports parser names, classes, routes, states, quality values and availability-related warnings.

It does not enumerate historical parse requests or source documents.

## 43. ParseFlowGraphOutput

`ParseFlowGraphOutput` exposes discovered parser graph metadata as HTML or JSON. The JSON includes parser names, route names, states, selected quality fields and aggregate counts.

It does not expose previous document contents because ParseFlow has no global parse-history store.

## 44. Graph metadata can still be operationally sensitive

Although the graph output is not document content, it reveals installed parser classes and available transformation capabilities.

A deployment may consider this implementation metadata sensitive. ParseFlow itself does not add a component-specific authorization check to this output.

## 45. Parser administration display

`ParserAdminDisplay` can:

- list discovered parsers
- expose parser class names
- expose route keys
- expose parser schemas
- read stored parser settings
- save parser settings
- reset parser settings

The JSON endpoint returns caught exception messages to the browser.

## 46. Parser administration authorization

`ParserAdminDisplay` contains no ParseFlow-specific `IUsermanager`, `IAccesscontrol` or permission check.

The host administration must ensure that only authorized users can access the display and its JSON endpoint.

This requirement is especially important because `save` and `reset` modify persistent settings.

## 47. Parser administration CSRF boundary

The current parser administration endpoint accepts JSON POST requests and does not implement a ParseFlow-owned CSRF token.

The host application must provide the browser request-protection mechanism at the administration boundary if the endpoint is reachable in a cookie-authenticated session.

## 48. Parser administration error messages

The JSON endpoint returns exception messages in an `error` field when an operation fails.

Depending on an extension parser, a schema provider or the active settings backend, an exception message could contain implementation details. Consumers should avoid placing secrets into exception strings.

## 49. Parser Explorer

`ParserExplorerAdminDisplay` calculates possible plans for selected semantic input and output states. It does not process a real uploaded document.

Its detail response can include generated PHP example code and parser-chain metadata.

## 50. Clipboard processing

The explorer can copy generated PHP integration code to the browser clipboard.

The generated code uses placeholders such as `/tmp/input.ext` and `$inputText = '...'`. It does not contain a previously parsed source document because the explorer does not operate on one.

Clipboard access occurs only after the user activates the copy action.

## 51. Browser session storage

The two ModularGrid-based admin views configure `sessionStorage` for UI state.

Current keys are:

```text
parseflow-parser-admin-grid-v3
parseflow-parser-explorer-grid-v5
```

Stored sections are limited to grid state such as query, filters and columns as configured by the UI.

These entries are browser-session data and are separate from server-side parser settings.

## 52. No ParseFlow cookies

The ParseFlow source in this package does not create its own cookies.

If the surrounding host application uses cookies for authentication, session management or preferences, those cookies belong to that host layer.

## 53. No ParseFlow content logging

The core runtime does not depend on `ILogger` and does not write document bodies, parse results or parser requests into a ParseFlow-specific log.

Errors are thrown to the caller, except that the parser administration endpoint catches errors and returns their messages as JSON.

A host application or extension parser can introduce additional logging outside this component.

## 54. No result cache

The current core parser service does not cache parse outputs. Repeating a parse request re-executes the selected parser plan.

## 55. Memory lifetime

Source values, intermediate payloads and final return payloads can remain in PHP memory for the lifetime of the request or process that owns the parser service.

In long-running workers, consumers should release references to large parser results when they are no longer needed.

## 56. Resource exhaustion considerations

The parser planner has graph expansion limits, but content parsers do not uniformly impose content-size limits.

Large files, deeply nested structured data, large images, large archives or large DOM documents can consume significant CPU and memory.

Operational input limits should be established at the ingress boundary for untrusted content.

## 57. Parser extension privacy responsibility

A third-party or project-specific parser can receive the same `ParserPayload` values as bundled parsers. It can also introduce new destinations such as network services or proprietary storage.

Its data processing must be documented with that parser implementation. Merely being discoverable through ParseFlow does not make its privacy behavior part of the ParseFlow core.

## 58. External-service route policy is a planning control, not a data-loss prevention system

`requiresExternalService` and `allowExternalServices` allow the planner to include or exclude routes based on declared route metadata.

The planner relies on the parser implementation to declare that metadata correctly. It does not inspect parser code or network traffic to determine whether a parser is actually local.

Parser authors must therefore classify external routes accurately.

## 59. Sensitivity is preserved by policy, not inferred by format

ParseFlow does not automatically mark a payload as personal, confidential or public based on file type or content.

Converting a confidential DOCX to plain text, or a location-bearing GPX file to CSV, does not make the resulting data less sensitive.

The consuming application must preserve its own data classification across transformations.

## 60. Deletion and retention responsibilities

For the core component, deletion concerns arise only when data has been deliberately persisted through:

- a file target
- a directory target
- retained temporary resources
- parser settings
- an extension parser with its own persistence

ParseFlow does not run a global retention job for those resources.

The owner of the storage location must define deletion and backup behavior.

## 61. Backups

ParseFlow has no backup subsystem.

Outputs written to filesystem paths or parser settings stored by the active `ISettingsStore` can become part of the host application's normal backup processes. Deleting the live copy does not automatically remove historical backups.

## 62. Data export

ParseFlow itself is a transformation service, not a user-data export product feature.

A caller can use parser routes to create export-friendly representations such as CSV, JSON, Markdown or files. The authorization and legal basis for such an export belong to the calling application.

## 63. No identity model

ParseFlow does not know which human user owns a source document or result. `ParserRequest` contains parser and transformation data, not a mandatory user identity.

User ownership, tenant separation and access decisions must be applied before the request reaches ParseFlow and when any persisted result is exposed later.

## 64. Recommended production controls for sensitive or untrusted data

A production integration should define controls appropriate to its use case, including:

- authorization before readable source paths are accepted
- authorization before writable target paths are accepted
- upload and file-size limits
- archive decompression limits
- image dimension and memory limits
- XML parser hardening appropriate to the deployed runtime
- content sanitization before browser rendering of HTML or SVG
- retention and deletion for output files
- retention and deletion for intentionally retained temporary files
- protection of parser administration endpoints
- CSRF protection for parser setting changes
- secret handling outside the generic parser settings UI
- memory and execution-time limits
- safe exception handling for filesystem paths and implementation details

## 65. Privacy review checklist for parser extensions

Before enabling an additional parser implementation, verify:

1. Which input content and metadata does it receive?
2. Does it read additional local files?
3. Does it call an external service?
4. Does it correctly mark `requiresExternalService`?
5. Which credentials does it need and where are they stored?
6. Does it create temporary files or directories?
7. Does it persist source or result content?
8. Does it log source content, result content or identifiers?
9. Which error details can reach callers or the administration UI?
10. Does it define file, archive, image or document size limits?
11. Does it return HTML or other active content that needs sanitization?
12. How are its retained data and credentials deleted?

## 66. Summary of the current core privacy boundary

The current ParseFlow core is primarily a local, transient transformation engine. It does not automatically send content to external services, does not maintain a document database, does not keep a parse history and does not log parser bodies through its own logger.

Its main privacy-sensitive boundaries are the content supplied by callers, arbitrary readable and writable filesystem paths, optional file and directory targets, metadata extraction, browser-facing active content such as raw SVG preview output, optional parser settings, and any additional parser implementations installed into the discovery graph.
