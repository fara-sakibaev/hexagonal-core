# Migrating ReadPortInterface

ReadPortInterface usage is a static-analysis error and the interface will be removed in the next breaking release. Replace aggregate persistence with RepositoryInterface or DTO query handling with QueryHandlerInterface.

Choose the replacement by what the operation returns and who owns its behavior.

## Aggregate persistence and retrieval

An Application outbound repository contract describes loading and storing domain aggregates. For example, given an existing Order aggregate:

```php
namespace Shop\Application\Port\Outbound;

use Hexagonal\Application\Port\Outbound\RepositoryInterface;
use Hexagonal\Type\InternalIdInterface;
use Shop\Domain\Order;

/** @extends RepositoryInterface<Order> */
interface OrderRepository extends RepositoryInterface
{
    public function find(InternalIdInterface $id): ?Order;
    public function save(Order $order): void;
}
```

The concrete persistence adapter belongs in Infrastructure and implements this contract. Preserve aggregate invariants and the existing persistence semantics when moving methods. A repository is not a generic substitute for DTO projections.

## Application DTO queries

An Application query handler owns answering a query with a DTO. Given an OrderSummary DTO and a FindOrderSummary query extending Query<OrderSummary>:

```php
namespace Shop\Application\Query;

use Hexagonal\Application\Query\QueryHandlerInterface;
use Shop\Application\DTO\OrderSummary;

/** @extends QueryHandlerInterface<OrderSummary, FindOrderSummary> */
interface OrderSummaryHandler extends QueryHandlerInterface
{
    public function handle(FindOrderSummary $query): OrderSummary;
}
```

These snippets describe contracts using existing consumer types; they are not complete applications.

For an existing Infrastructure read adapter, first separate query orchestration from data access. Keep infrastructure details in Infrastructure and put the query-handler responsibility in Application. Review dependencies against the project's current layer rules. Merely replacing ReadPortInterface with QueryHandlerInterface on an Infrastructure class creates a layer violation.

## Migration checks

Update indirect consumer interfaces, their implementations, native parameter/property/return types and PHPDoc types, including tests. Remove obsolete imports after migrating the actual type references.

Run analysis over the same consumer scope before and after migration. Fix genuine generic, handler and architecture errors independently. The deprecation release does not relax these checks.

The interface uses a prose deprecation notice so analyzer-specific built-in deprecated-symbol handling does not add a second error-level diagnostic. The integration's dedicated nonfatal diagnostic is the reporting contract.

The ordinary PHPStan integration selects the human formatter. Explicit consumer formatter settings and CLI overrides remain effective; use the package formatter to include ReadPort errors in its output.

Psalm shows these diagnostics with \`vendor/bin/psalm --show-info=true\`. They use INFO by default. \`--show-info=false\` hides details; explicit consumer issue configuration remains authoritative.

For structured diagnostics, run vendor/bin/phpstan analyse --error-format=hexagonalJson. ReadPort findings are reported as errors and make analysis fail. Psalm reports DeprecatedReadPort at error severity by default; targeted issue configuration remains available during migration.
