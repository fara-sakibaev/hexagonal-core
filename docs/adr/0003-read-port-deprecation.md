# Deprecate the generic read port

ReadPortInterface will be deprecated in the nearest release and removed in the following breaking release. During deprecation, PHPStan and Psalm must report nonfatal warnings: deprecated usage alone must not fail analysis or CI; after removal, remaining usages must cause analysis errors. This gives consumers a migration period before the contract disappears.

Migration follows the responsibility: use RepositoryInterface for aggregate persistence and retrieval, or QueryHandlerInterface for application queries returning DTOs. Query handlers belong in Application; migrating an Infrastructure read adapter requires reviewing its dependencies and responsibility, rather than merely replacing its marker.

The agreed warning text is: "ReadPortInterface is deprecated. Consider using RepositoryInterface for aggregate persistence or QueryHandlerInterface for application queries. ReadPortInterface will be removed in the next breaking release; remaining usages will cause analysis errors."

Detection covers extends/implements, indirect inheritance through consumer interfaces, parameter/property/return types, and PHPDoc references to the contract. Diagnostics identify the source file and line.

Detection is enabled with the ordinary PHPStan/Psalm integration and does not require hexagonal.json or architecture checking. Analyzed test code is included because interface removal affects tests too.

PHPStan warning delivery uses library-provided formatters backed by collected analysis data. The ordinary integration selects the human-readable formatter by default. Consumers wanting structured warnings use --error-format=hexagonalJson; the stock JSON formatter does not display these warnings. Warning records must not inflate error counts or change the exit status determined by actual errors.

Psalm uses its native info severity. Consumers enable message visibility with --show-info=true. Detection is automatic, but visibility follows analyzer reporting configuration; it is not guaranteed for every output format. Direct writes to stderr from analysis rules are not the chosen reporting mechanism.

Verification must cover direct and inherited contract usage, native type and PHPDoc references, analyzed tests without architecture configuration, warning-only successful exits, failures from real errors alongside warnings, valid structured output, and repeat analysis with cached results.

The design is agreed. This decision records planned behavior, not a completed implementation; removal belongs to the following breaking release rather than the initial deprecation change.
