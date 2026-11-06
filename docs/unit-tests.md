# ParseFlow unit tests

## PHPUnit coverage suite

The `test/` directory contains unit tests for parser sources, inputs, outputs, targets, DTOs,
route discovery, graph planning, execution, scoring, temporary resources, configuration
normalization, explorer result generation and all discoverable single-route parsers.
The parser catalogue test enumerates every implementation under `src/Parser/` and checks
its declared route and actual execution, or the documented unavailable behavior when an
optional PHP extension is not installed. Archive, image and XML fixtures are created
locally and do not call external services.

The suite uses `PHPUnit\Framework\TestCase` and the `#[DataProvider]` attribute supported
by PHPUnit 11.5 and 12.5. No dependency on internal PHPUnit runner APIs is introduced.
Run the suite against **both** PHPUnit versions on a PHP installation supported by each
version. From the ILIAS root, using the corresponding installed test runner:

```sh
vendor/bin/phpunit -c components/Base3/ParseFlow/phpunit.xml --testsuite ParseFlow
XDEBUG_MODE=coverage vendor/bin/phpunit -c components/Base3/ParseFlow/phpunit.xml \\
  --coverage-text --coverage-clover /tmp/parseflow-coverage.xml
```

For standalone BASE3 the same configuration is under `plugin/ParseFlow/phpunit.xml`.
The `<source>` configuration includes the **entire** `src/` tree, including files not
executed by the tests, so the reported percentage is not inflated by excluding
uncovered production code. A real PHPUnit run plus an available PCOV or Xdebug
coverage driver are required to establish a coverage percentage. The HTML and parser
administration surfaces must also be checked against the precise deployed source
revision, since the surrounding project may change their constructor dependencies.
