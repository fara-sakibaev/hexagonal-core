# Hexagonal Core

PHP 8.5 contracts for application and domain layers.

## Analyzer integration

Install this package and either PHPStan or Psalm in the consumer root. Run `vendor/bin/hexagonal-setup` from that root. It detects `phpstan.neon`, `phpstan.neon.dist`, `phpstan.dist.neon`, `psalm.xml`, `psalm.xml.dist`, plus a custom config passed with `-c` or `--configuration` in root Composer scripts. Multiple configs require `--config PATH`. With no config, choose `--analyzer phpstan` or `--analyzer psalm` for a starter. The default run previews changes; `--apply` writes them; `--dry-run` always previews.

The setup only edits analyzer configuration. Add a root Composer script and CI step, for example `"analyse": "phpstan analyse -c phpstan.neon"` or `"analyse": "psalm -c psalm.xml"`, then run `composer analyse` in CI. Dependency scripts do not run automatically in consumers. The checks are optional until the consumer enables one of these commands.

Handler conventions: `CommandHandlerInterface<TCommand>` declares `public handle(TCommand): void`; `QueryHandlerInterface<TResult, TQuery>` declares `public handle(TQuery): TResult`; domain and integration event handlers declare `public handle(TEvent): void`. The marker interfaces stay empty so handlers can use concrete parameter and return types. PHPStan and Psalm integrations report missing and incompatible methods.

## Breaking query and read-port migration

`Query<TResult>` now requires `TResult` to extend `DTO`. Wrap scalar or array results in an application DTO and update query and handler annotations. `QueryBusInterface::ask()` retains the concrete DTO return type in static analysis. `ReadPortInterface<TInput>` now accepts only a `Query<DTO>` subtype. Move command operations to a separate outbound command port. These two changes require a breaking release for affected consumers.

```php
/** @extends Query<UserView> */
final readonly class FindUser extends Query {}
final readonly class UserView extends DTO {}
/** @implements QueryHandlerInterface<UserView, FindUser> */
final class FindUserHandler implements QueryHandlerInterface {
    public function handle(FindUser $query): UserView { return new UserView(); }
}
```

## Aggregate identity and optional outbox

Aggregates expose a readable `InternalIdInterface` property; concrete aggregates may use a narrower ID type. Keep identity stable for the aggregate lifetime. A get-only property contract alone cannot enforce immutability of its backing storage.

`OutboxCoordinator` takes a transaction adapter, outbox adapter and ID factory. Its transaction must cover aggregate persistence and outbox append on the same atomic resource. It reads pending domain events, commits aggregate plus records, then acknowledges events. On rollback events remain pending. `OutboxRelay` publishes records and marks them delivered afterward. A crash between those steps can redeliver the same ID; consumers must deduplicate by `OutboxRecord::eventId`. The package supplies no SQL schema or broker adapter. Adoption is optional.

See [consumer architecture analysis](docs/architecture-analysis.md) for context discovery, role checks and explicit test exemptions.

## Read-port deprecation

ReadPortInterface remains usable during the migration period and will be removed in the next breaking release. Ordinary analyzer integration detects deprecated references without hexagonal.json, including analyzed tests.

PHPStan and Psalm report deprecated references as errors by default; analysis exits nonzero and includes source locations. PHPStan JSON output includes ReadPort findings in the standard file messages and error totals.

Psalm reports ReadPort findings as errors by default. Existing aliases, inheritance, PHPDoc and native references are covered; unrelated same-name types are excluded.

See [read-port migration](docs/read-port-migration.md) for aggregate repository and Application DTO query examples. Infrastructure adapters need responsibility and dependency review before migration. The package requires phpstan/phpdoc-parser ^2 so either analyzer can parse PHPDoc types independently.
