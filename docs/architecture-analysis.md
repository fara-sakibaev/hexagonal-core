# Consumer architecture analysis

Architecture checks are opt-in alongside existing handler checks. Create `hexagonal.json` in the consumer project:

```json
{
  "search_paths": ["src", "packages"],
  "context_roots": ["src/App", "packages/Orders/src"],
  "test_paths": ["tests"]
}
```

Paths are relative to this JSON file; use existing directories. Remove entries that do not exist. Duplicate/overlapping entries within one list and context roots outside search paths are rejected. A test path cannot exclude a whole context root. Explicit test subdirectories take precedence over context checks; class names and namespaces do not grant exemptions.

Run `vendor/bin/hexagonal-setup --architecture hexagonal.json --dry-run` to preview candidates and analyzer integration. Select `--analyzer phpstan` or `--analyzer psalm` when needed; use `--config PATH` for an existing nonstandard analyzer configuration. Review declared/undeclared candidates, edit context_roots, then use `--apply`. Existing analyzer paths are preserved: extend them to cover all context PHP files. Starter configurations use the configured search and test paths, not a hardcoded src directory.

Manual PHPStan integration:

```neon
includes:
    - vendor/fara-sakibaev/hexagonal-core/phpstan-extension.neon
parameters:
    hexagonalArchitecture: %currentWorkingDirectory%/hexagonal.json
    paths:
        - src
        - packages
        - tests
```

Manual Psalm integration, inside plugins:

```xml
<pluginClass class="Hexagonal\StaticAnalysis\Psalm\HandlerPlugin">
    <architecture path="hexagonal.json"/>
</pluginClass>
```

Psalm resolves this path against its configuration base directory. Set projectFiles to cover the context and test paths. Both analyzers use their own source ancestry, not runtime autoload of consumer classes.

## Rules

| Layer | Allowed role families | Cardinality |
| --- | --- | --- |
| Application | Application, Type, Validation | Exactly one |
| Domain | Domain, Type | Exactly one |
| Infrastructure | Ports, Type, Validation | Zero or one |

Infrastructure ports are CommandBus, QueryBus, DomainEventPublisher, IntegrationEventPublisher, ReadPort, Repository, EventIdFactory, EventPublisher, Outbox and Transaction. Other known roles remain forbidden even though unclassified infrastructure helpers are allowed.

Named concrete and abstract classes, interfaces and enums are classified. Traits have no standalone role, but anonymous classes inside them are still forbidden. Explicit test paths are exempt from architectural checks, not ordinary analyzer/handler checks.

InternalUid and ExternalUid resolve through their identifier interfaces to InternalId and ExternalId. These roles absorb Id; combining both origins is ambiguous. Other independent matches never use first-match precedence.

## Diagnostics

| Category | Resolution |
| --- | --- |
| hexagonal.unknownRole | Give the Application/Domain declaration an appropriate contract |
| hexagonal.ambiguousRole | Separate independent responsibilities |
| hexagonal.incompatibleLayer | Move the declaration or correct its contract |
| hexagonal.outOfLayer | Place the declaration inside a layer |
| hexagonal.anonymousClass | Use a named class in production contexts |
| hexagonal.undeclaredContext | Review and explicitly declare the discovered root |
| hexagonal.incompleteScope | Include the context PHP file in analyzer coverage |
| hexagonal.invalidScope | Fix configuration paths or JSON |

PHPStan exposes categories as identifiers; Psalm reports ArchitectureIssue with the same category in its message. Declaration diagnostics retain file/line locations. Discovery diagnostics identify the affected path, even when reported on another analyzed file. Discovery runs on cached analyses too. Parents of any single layer directory are candidates; Composer package roots are not automatically contexts. Dependencies in vendor and .git are excluded from discovery.

To migrate existing consumers, first configure search paths and inspect candidates, declare roots/test paths, then resolve diagnostics. To disable architecture enforcement remove hexagonalArchitecture/the architecture plugin element; existing handler validation remains active. These checks classify declarations; they do not enforce import/dependency direction.
