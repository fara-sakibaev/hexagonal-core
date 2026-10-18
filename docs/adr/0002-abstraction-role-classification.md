# Abstraction role classification in consumer applications

Application and Domain types must have exactly one abstraction role. Infrastructure types may have no role, but must not combine roles. Classification covers named concrete and abstract classes, interfaces, and enums; traits are excluded. Unknown required roles and multiple roles produce analyzer diagnostics instead of aborting analysis.

Roles must also be compatible with the containing layer. Infrastructure may implement Application and Domain ports, but may not declare domain models, commands, queries, or use-case handlers merely because they have a single known role.

Identifier roles follow contracts: InternalUid has the InternalId role through InternalIdInterface, and ExternalUid has the ExternalId role through ExternalIdInterface. They do not have separate UID roles.

Context root discovery searches configured first-party code areas for Application, Domain, or Infrastructure directories. The immediate parent of even one such layer is a candidate; discovery does not require all three layers. Context roots are explicitly declared in configuration, and newly discovered undeclared candidates produce diagnostics. Composer package roots and source roots are not assumed to be context roots.

An external RoleMapper collects matches across all role families, applies explicit specialization, and resolves the result to a single role or reports unknown or ambiguous classification. Enums remain role catalogs. Analyzer adapters supply inheritance information from PHPStan or Psalm rather than relying on runtime is_a() calls. Rules translate classification results into diagnostics according to the containing layer.

InternalId absorbs the inherited Id role, as does ExternalId. A type implementing only IdInterface has the Id role. Combining InternalId and ExternalId is ambiguous. This specialization is explicit for identifier roles, not a general first-match precedence rule.

Type roles are allowed in Application, Domain, and Infrastructure. Validation roles are allowed in Application and Infrastructure; Domain uses domain roles and Type, with domain rules expressed through Policy. These restrictions classify declarations; dependency rules are a separate concern.

Anonymous classes are forbidden inside contexts and produce a dedicated diagnostic regardless of their implemented roles. They are allowed in test code, identified by explicit test_paths configuration. Test paths are exempt from architectural role classification and the anonymous-class prohibition, while retaining ordinary PHPStan or Psalm analysis. Class names and namespace naming conventions do not implicitly grant test exemptions.

A classified type inside a declared context but outside Application, Domain, or Infrastructure produces an out-of-layer diagnostic. Explicit test paths are exempt.

The declaration compatibility matrix is:

| Layer | Allowed roles | Required number of roles |
| --- | --- | --- |
| Application | Application, Type, Validation | Exactly one |
| Domain | Domain, Type | Exactly one |
| Infrastructure | Application/Domain ports, Type, Validation | Zero or one |

Infrastructure's allowance for zero roles does not permit a known role from a disallowed layer. Multiple independent roles are always a diagnostic, including in Infrastructure. Only the explicit identifier specialization collapses inherited role matches.
