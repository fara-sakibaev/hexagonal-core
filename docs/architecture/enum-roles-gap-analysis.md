# Enum-ролей и конфигурационных параметров: gap analysis

Дата аудита: 2026-10-18. Область: текущая рабочая копия `fara-sakibaev/hexagonal-core`.

## 1. Краткий вывод

**Модель не готова к принятию.** Реализация архитектурных ролей уже задаёт нужную основу: роли выводятся из контрактов и их транзитивных предков; Application и Domain требуют одну роль; Infrastructure допускает ноль; известная роль неверного слоя и несколько независимых ролей отклоняются. Эти правила приняты в ADR-0002 и реализованы.

Не найдено ни одного enum-потребителя в `src/` или интеграции, ни порта, чья конфигурация была бы выражена enum’ом, ни enum-конфигурации адаптера/Infrastructure. Пять enum’ов самого пакета — каталоги классификатора и enum слоя, не примеры потребительских контрактов. Поэтому вводить все четыре публичных маркера как уже обоснованное решение нельзя. Минимальный следующий шаг — ответить на вопросы в §8 на конкретных примерах потребителей. Вероятно, достаточно сохранить `DomainEnumInterface` как проверяемого кандидата, а конфигурационные маркеры отложить до появления доказанных контрактов; это рекомендация, а не принятое правило.

Техническая осуществимость подтверждена на PHP 8.5.9: интерфейс может расширять `UnitEnum`, pure/backed enum реализуют такой интерфейс и цепочку его специализации. Обычный класс не должен реализовать `UnitEnum`-основанный контракт по правилам PHP; отдельный негативный runtime-сценарий не запускался. Основной технический гэп — текущий mapper не знает специализацию новых ролей: Adapter, реализующий дочерний маркер, даст совпадения и с дочерним, и с родительским контрактом, что сейчас классифицируется как неоднозначность.

## 2. Область, версии, источники и ограничения

- Это Composer library с PSR-4 `Hexagonal\\ => src/`; приложение-потребитель отсутствует. Единственная интеграция — `integrations/symfony-validator`, использует рабочую копию библиотеки через path repository. Её lock фиксирует `fara-sakibaev/hexagonal-core` как `dev-main`, reference `fadc5ec38627cc8be879143b4f0f3a112ccd4a36`; отдельной опубликованной версии или установленной копии в корневом `vendor/fara-sakibaev/hexagonal-core` нет. Исследован исходник текущего checkout, не latest release.
- `composer.json`: PHP `^8.5`, PHPStan `^2`, Psalm `^6`. Запуск: PHP 8.5.9, PHPStan 2.3.1, Psalm 6.20.0. Lock корня закрепляет версии инструментов (совпадают с установленными).
- Production: `src/`; потребительская интеграция: `integrations/symfony-validator/src/`; тесты и fixtures: `tests/`, `integrations/symfony-validator/tests/`. `rg --files` и `rg -n '\benum\s+[A-Za-z_]' src integrations` использованы для inventory; все найденные production enum’ы перечислены в §3. В самой интеграции enum’ов нет.
- Проверены применимые пользовательские инструкции; `find .. -name AGENTS.md` не нашёл дополнительных файлов. Нормативные источники: [ADR-0002](../adr/0002-abstraction-role-classification.md), [руководство по архитектурному анализу](../architecture-analysis.md), OpenSpec `openspec/specs/static-analysis-setup/spec.md` и активное изменение `openspec/changes/enforce-context-abstraction-roles/` (proposal/design/spec/tasks/verification), плюс обсуждаемый документ [enum-roles-and-configuration-parameters-discussion.md](../research/enum-roles-and-configuration-parameters-discussion.md). Последний явно помечен draft, не ADR/spec.
- В рабочем дереве до аудита были изменённые и неотслеживаемые файлы, включая сам обсуждаемый документ. Они сохранены без изменений; создан только этот отчёт. Отчёта с таким именем до создания не было.
- Нет репозитория-потребителя, его Composer lock/config, его baseline или данных о реальном использовании ролей. Это исключает вывод о достаточности модели для всех downstream проектов.

### Нормативный статус

| Требование | Статус документа | Реализация и тест | Проверка в этом аудите |
|---|---|---|---|
| Domain/Application ровно одна роль; Infrastructure 0..1 | Принято: ADR-0002, active OpenSpec role spec | `Layer::violation`; `tests/abstraction-roles.php` | Ролевой тест прошёл |
| Известная роль совместима со слоем | Принято | `Layer::allows`; unit и analyzer fixtures | Ролевой и analyzer tests прошли |
| Только явно оговорённая специализация InternalId/ExternalId поглощает общий Id | Принято: ADR-0002 | `RoleMapper`; проверки Internal/External/конфликта | Ролевой тест прошёл |
| Enum получает архитектурную роль по новым marker contracts | Только проект предложения | Контрактов/каталога ролей/тестов нет | Не применимо, модель не принята |
| Domain enum разрешено использовать в Application DTO без смены роли | Предложено в обсуждении; следует из направления зависимости, но отдельной enum-гарантии нет | Не найден потребительский enum/DTO-пример или тест этого сценария | Не проверялось на реальном consumer |
| Маркеры наследуют UnitEnum; Adapter специализирует Infrastructure | Предложено | Текущая базовая `AbstractionRoleInterface` расширяет UnitEnum, но новых маркеров нет | Изолированная языковая проверка прошла на PHP 8.5.9 |
| Обнаружение деклараций, предков и неоднозначностей PHPStan/Psalm | Принято действующими архитектурными спецификациями | Collector/Psalm visitor + analyzer fixtures | `test:architecture:analysis` прошёл |
| Роль должна проверяться во всём consumer production code | Анализ opt-in, scope явно конфигурируется; не глобальная гарантия | PHPStan default architecture null, Psalm config без architecture элемента | Подтверждена ограниченность scope; тесты проверяют configured paths |

## 3. Инвентаризация production enum’ов

Точного FQCN для внутренних enum’ов нет в исходнике, поскольку перечисления глобальные в пространстве имён `Hexagonal\\StaticAnalysis\\AbstractionRole`. Смысл установлен по объявлению и использованию в mapper/policy, а не по одному имени.

| FQCN / объявление | Слой / роль классификатора | Что означает case и потребители | Кандидат обсуждаемой модели |
|---|---|---|---|
| `Hexagonal\\StaticAnalysis\\AbstractionRole\\ApplicationAbstractionRole`, [ApplicationAbstractionRole.php:30](../../src/StaticAnalysis/AbstractionRole/ApplicationAbstractionRole.php:30) | Вне consumer context; не получает слой по архитектурному анализатору пакета. Сам enum реализует `AbstractionRoleInterface`; сам не записан как role contract. | Каталог существующих ролей Command, DTO, ports, handlers, outbox. Используется `RoleMapper::catalog()` и tests. Cases называют типы-контракты, не значения предметной области. | Ни один из четырёх. Это внутренний технический role catalog. |
| `Hexagonal\\StaticAnalysis\\AbstractionRole\\DomainAbstractionRole`, [DomainAbstractionRole.php:13](../../src/StaticAnalysis/AbstractionRole/DomainAbstractionRole.php:13) | Вне consumer context; не классифицируется. Реализует `AbstractionRoleInterface`. | Каталог AggregateRoot, DomainEvent, Policy, ValueObject; используется mapper/tests. | Не `DomainEnumInterface`: cases описывают роли анализатора, не бизнесовые значения. |
| `Hexagonal\\StaticAnalysis\\AbstractionRole\\TypeAbstractionRole`, [TypeAbstractionRole.php:12](../../src/StaticAnalysis/AbstractionRole/TypeAbstractionRole.php:12) | Вне consumer context; не классифицируется. Реализует `AbstractionRoleInterface`. | Каталог InternalId, ExternalId, Id; предки UID и identity интерфейсы разрешаются mapper’ом. | Ни один предложенный enum marker. |
| `Hexagonal\\StaticAnalysis\\AbstractionRole\\ValidationAbstractionRole`, [ValidationAbstractionRole.php:17](../../src/StaticAnalysis/AbstractionRole/ValidationAbstractionRole.php:17) | Вне consumer context; не классифицируется. Реализует `AbstractionRoleInterface`. | Каталог validation contracts/value types, включая `ViolationCode`; проверяется mapper’ом и role test. Это не enum и не конфигурация. | Ни один. `ViolationCode` — обычный класс, дополнительный сигнал не превращать технические варианты автоматически в enum-роли. |
| `Hexagonal\\StaticAnalysis\\AbstractionRole\\Layer`, [Layer.php:10](../../src/StaticAnalysis/AbstractionRole/Layer.php:10) | Вне consumer context; не классифицируется. Не реализует архитектурный role marker. | Значения Application/Domain/Infrastructure выбирают разрешённые role family и сегмент пути в `DeclarationPolicy`. Это технические значения алгоритма классификации. | Ни один. Не является конфигурационным enum’ом по доступному контракту. |

Охват подтверждён: `composer.json` autoload root — весь `src/`, интеграция имеет собственный `src/`, поиск `enum` по обоим каталогам нашёл пять объявлений и ноль интеграционных enum’ов. Архитектурный analyzer scope в consumer конфиге должен включать все нужные first-party пути; документация требует расширить `paths`/`projectFiles`, а `ContextDiscovery` сообщает о неполном охвате контекстных файлов. В репозитории нет такого consumer config.

### Конфигурация и вызовы

Поиск `config|configuration|parameter|timeout|endpoint|dsn|host|tls|queue|credentials|secret|sort|filter` по production PHP и тестам обнаружил analyzer-конфигурацию (`ScopeConfiguration`, PHPStan/Psalm setup), но не runtime-настройки адаптера/порта. Реальные порты пакета: Repository, ReadPort, buses, publishers, outbox/transaction. Контракты принимают DTO/Query/Command/события или операционные транзакционные параметры, но отдельного configuration contract/enum нет. Сам факт наличия analyzer config не обосновывает enum-маркер runtime-параметров.

Нельзя привести реальный пример ключа (`Endpoint`, `Timeout`), значения настройки (`TlsMode`), параметра вызова (`SortDirection`) либо предметного enum’а из доступного production scope: таких enum’ов здесь нет. Иллюстрации черновика доказательством не считаются.

## 4. Механика классификатора и покрытие

- `RoleMapper::catalog()` объединяет четыре role catalogs. `resolve()` строит нормализованный набор имени типа и переданных предков, выбирает каждый совпавший `contract()`; он не применяет first-match приоритет. Единственное схлопывание — удалить общий `Id`, когда найден `InternalId` или `ExternalId`.
- PHPStan `ArchitectureCollector` ищет `PhpParser\Node\Stmt\ClassLike`, отражение предоставляет транзитивных ancestors; PHPStan использует FQCN через reflection. Psalm проходит `ClassLike`, запускает `NameResolver`, берёт `parent_classes`, `parent_interfaces`, `class_implements`. Поэтому enum-узел входит в общий обход. Сравнение FQCN mapper’ом нормализует слеши и регистр; alias разрешается parser/name resolver до FQCN. Нынешний тест не содержит отдельного import-alias enum case.
- `DeclarationPolicy` проверяет только файл внутри настроенного context root и только декларацию в Application/Domain/Infrastructure; traits исключены. Вне контекста ролей не требует. Новые маркеры должны быть добавлены в mapper catalog и правила слоя, иначе они неизвестны mapper’у.
- Статические rule catalogs (`ApplicationAbstractionRole` и др.) не равны «ролям самих enum declarations». Алгоритм ищет совпадения для имени проверяемого типа и всех ancestors. Следовательно, role marker interface, объявленный в consumer Application/Domain, сам должен удовлетворить cardinality; маркер, объявленный в Infrastructure, с текущим правилом нулевой роли пройдёт как Infrastructure без совпадений. Для role-contract declarations потребуется явное решение, тест и слой размещения.
- Domain enum в Application DTO — ссылка на тип из Application, а не дополнительная роль enum. Анализатор классифицирует файл объявления enum и файл DTO независимо; DTO должен иметь свою Application role, enum — свою Domain role. Маркер не запрещает межконтекстную зависимость: ADR прямо отделяет классификацию ролей от dependency/import direction.

## 5. Реестр гэпов

### G1 — Не определён смысл ConfigParameter

- **Категория/приоритет:** решение модели; блокирует принятие и внедрение.
- **Основание:** draft говорит «параметры конфигурации», одновременно приводит и компонентные настройки, и credentials field; прямо оставляет key/value/runtime metadata без контракта. Принятого определения нет.
- **Ожидание/факт:** нужно выбрать: enum перечисляет ключи/имена настроек; enum перечисляет допустимые значения конкретной настройки; либо это иной runtime type. В коде нет примера, позволяющего выбрать. Операционный аргумент вызова (например, sort/filter для одного query) не становится config только из-за enum-представления.
- **Доказательства:** обсуждение §«Проблема», §«Модель понятий»; `rg` по `src/` и интеграции не находит конфигурационных enum’ов или consumer settings.
- **Подтверждение/последствие:** отсутствие потребителя подтверждено в указанной области; нужность в downstream проектах не опровергнута. Без решения разные несходные типы получат один и тот же marker.
- **Минимальное действие:** выбрать semantics и naming; `ConfigKey` рассмотреть для ключей, не принимать автоматически. Критерий: на каждом фактическом примере можно однозначно сказать «ключ», «допустимое значение», «аргумент операции» или «другое».
- **Зависимости:** перед G3 и набором ролей.

### G2 — Application enum’ам, не являющимся Domain и config порта, нет ясной роли

- **Категория/приоритет:** решение модели; блокирует внедрение, если требование ADR распространяется на любой enum в Application.
- **Основание:** ADR-0002 требует Application ровно одну роль; сейчас catalogs не имеют generic enum role. Возможные значения сортировки/представления в черновике не являются предметными по определению.
- **Ожидание/факт:** для реального `SortDirection` нет подтверждённого типа/потребителя в репозитории и нельзя решить, следует ли это считать параметром операции, UI/presentation понятиями или Application role. Domain marker будет неверным обходом; config-port marker тоже не подходит автоматически.
- **Доказательства:** `Layer::violation()` выдаёт unknownRole для Application без роли; каталог `ApplicationAbstractionRole`; inventory §3.
- **Подтверждение/последствие:** правило подтверждено; дефицит роли для реального consumer-типа — гипотеза, не воспроизведённый дефект.
- **Минимальное действие:** предоставить конкретный Application enum и его Command/Query/DTO потребителя; решить: расширить существующий контракт, ввести узкий Presentation/Query role, разрешить осознанно generic enum role, или тип не должен быть enum. Проверяемое свойство — слой и роль соответствуют смыслу; общий маркер повышает риск формального обхода.
- **Зависимости:** G1; не добавлять generic role без доказанного примера.

### G3 — Неподтверждённая потребность в PortConfig и InfrastructureConfig

- **Категория/приоритет:** решение модели; блокирует принятие соответствующих public contracts.
- **Основание:** соответствующие маркеры только предложены; новые public contracts повышают API/support cost.
- **Ожидание/факт:** для `PortConfigParameterInterface` требуется реальный порт, контрактное требование к настройке, место задания параметра и consumer, который использует его вне знания конкретного адаптера. Ни одного примера не найдено. Все production конфигурационные hits относятся к настройке анализатора. Для `InfrastructureConfigParameterInterface` вне adapter тоже нет примера.
- **Доказательства:** порты перечислены в [ApplicationAbstractionRole.php](../../src/StaticAnalysis/AbstractionRole/ApplicationAbstractionRole.php); integration `AttributedInputValidator` и тестовые `RegistrationInput`/`TransferInput` не конфигурируют ports.
- **Подтверждение/последствие:** отсутствие в library/integration подтверждено поиском; вывод о бесполезности во всех клиентах не делается. При принятии без примера портовая политика может стать деталью adapter, а несвязанные поля «configuration» объединятся.
- **Минимальное действие:** отложить оба публичных интерфейса до consumer use case. Для каждого кандидата записать port declaration, setter/factory/composition location, consumer и замену adapter. Устойчивый смысл при смене adapter необходим, но сам по себе недостаточен без контракта.
- **Зависимости:** G1.

### G4 — Специализация Adapter приводит к двум role matches

- **Категория/приоритет:** дефект проектируемой реализации; блокирует внедрение, пока не выбрано правило.
- **Основание:** принятый ADR схлопывает только явно названные identity специализации; остальные совпадения независимы и ambiguous. Черновик предлагает `AdapterConfigParameterInterface extends InfrastructureConfigParameterInterface`.
- **Ожидание/факт:** mapper получит обе роли через analyzer ancestors и вернёт две. `Layer::violation()` выдаст `hexagonal.ambiguousRole`, даже в Infrastructure. Автоматическое «самая глубокая роль побеждает» не зафиксировано и может скрыть sibling специализации.
- **Доказательства:** [RoleMapper.php](../../src/StaticAnalysis/AbstractionRole/RoleMapper.php), [Layer.php](../../src/StaticAnalysis/AbstractionRole/Layer.php), [tests/abstraction-roles.php](../../tests/abstraction-roles.php) проверяют явное поглощение `Id`, независимую неоднозначность и конфликт InternalId+ExternalId.
- **Подтверждение/последствие:** вывод по конкретному коду; гипотетический новый маркер не исполнялся. Явный role specialization map мог бы схлопывать только Adapter -> Infrastructure; две sibling специализации всё равно ambiguous.
- **Минимальное действие:** принять, что Adapter — один самый точный контракт с наследованием и добавить явное правило поглощения родителя; тестировать child alone, explicit parent+child, два child с общим предком, pure/backed enums. Не обобщать приоритет на все role families.
- **Зависимости:** G1, G3.

### G5 — Gate не является обязательным или глобальным

- **Категория/приоритет:** пробел охвата gate; блокирует обещание обязательной проверки в consumer.
- **Основание:** архитектурная документация определяет проверки как opt-in и scope по `search_paths`, `context_roots`, `test_paths`.
- **Ожидание/факт:** новый enum в файле вне context root не получает диагностику. PHPStan extension default `hexagonalArchitecture: null`; корневой Psalm config не включает `architecture` в plugin. Это намеренная конфигурационная граница, не баг принятой архитектуры. Consumer может пропустить файл, если анализатор не включает путь; настроенный context обнаружит `incompleteScope` только для заявленного scope.
- **Доказательства:** [phpstan-extension.neon](../../phpstan-extension.neon), [psalm.xml](../../psalm.xml), [docs/architecture-analysis.md](../architecture-analysis.md), `ArchitectureRule`, `ArchitectureCheck`, `ScopeConfiguration`/`ContextDiscovery`.
- **Подтверждение/последствие:** подтверждено конфигурацией и тестом. В корневой CI запускаются анализаторы пакета, но не задан consumer architecture JSON. CI тестирует архитектурный движок на временных fixtures, не полноту неизвестного downstream приложения.
- **Минимальное действие:** не менять opt-in без отдельного решения. Потребителю, требующему гарантии, включить архитектурный config/plugin, покрыть все first-party пути, объявить context roots и запускать их в CI. Критерий — тест scope, который показывает диагностирование корректно размещённого enum с отсутствующей/неверной ролью в каждом поддерживаемом analyzer.
- **Зависимости:** scope договора с библиотекой/consumer; отдельно от naming.

### G6 — Нет тестов для enum-role контрактов и наследования marker’ов

- **Категория/приоритет:** тестовое покрытие; блокирует реализацию, не принятие смыслов само по себе.
- **Основание:** действующие tests покрывают enums как class-like declarations косвенно через общий Node/ClassLike анализ, но текущие fixtures архитектуры объявляют class/interface, не enum-role.
- **Ожидание/факт:** существующая проверка analyzer parity не доказывает корректную роль pure/backed enum, alias, поведение для unknown enum role, иерархию новых marker’ов или использование Domain enum из Application DTO.
- **Доказательства:** `tests/architecture-analysis.php` fixtures; `tests/abstraction-roles.php`; активный OpenSpec verification описывает общие классы и роли, не предложенные enum contracts.
- **Подтверждение/последствие:** отсутствие конкретных сценариев подтверждено чтением fixtures. Базовый AST traversal enum-узлов присутствует.
- **Минимальное действие:** после принятия модели добавить pure/backed fixtures в оба analyzer, отсутствие/неверный слой/ambiguous roles, транзитивную специализацию, alias, обычный интерфейс рядом с UnitEnum-based role, Domain enum в DTO и skip/scope case. Гонять тесты на PHP 8.5 и обеих заявленных версиях analyzer.
- **Зависимости:** G1–G4 определяют ожидания.

## 6. Рекомендация по маркерам

| Маркер | Рекомендация | Основание и следствие |
|---|---|---|
| `DomainEnumInterface` | **Уточнить; оставить кандидатом.** | Понятие предметного значения согласуется с принятым Domain role; enum в DTO не меняет владельца. Но в этой библиотеке нет потребительского enum-примера и отдельной Domain role для него. Если принять, нужен типизированный контракт и тест; маркер заявляет семантику, но не доказывает предметность. Не должен разрешать межконтекстную зависимость автоматически. |
| `PortConfigParameterInterface` | **Отложить.** | Нет port configuration consumer, места задания и потребителя. Сначала отличить contract-level setting от аргумента каждой операции и adapter detail. Иначе добавится публичный API без подтверждённого значения. |
| `InfrastructureConfigParameterInterface` | **Отложить.** | В области поиска нет runtime enum конфигурации Infrastructure. Наличие общего Infrastructure слоя не доказывает необходимость отдельного конфигурационного enum-маркера. Возврат к вопросу при concrete component/config consumer. |
| `AdapterConfigParameterInterface` | **Уточнить и отложить вместе с базовой Infra ролью.** | Смысл «adapter-specific» может быть полезен только при реальном адаптере/настройке. Наследование выразительно, но mapper обязан иметь явную specialization map. Не применять общий most-specific precedence: sibling roles с общим предком должны оставаться неоднозначными. |

Naming вопрос для G1: если enum объявляет имена полей/ключей («endpoint», «timeout»), точнее `ConfigKey`; если enum перечисляет значения одной настройки («TLS strict/disabled»), `ConfigValue`/`ConfigOption` точнее; если это сортировка/фильтр отдельного запроса, это operation/query input, не config. Это варианты терминологии, не переименование принятое аудитом.

## 7. Гарантии и минимальный порядок работ

### Где обеспечивается свойство

| Свойство | Механизм и граница |
|---|---|
| Маркер расширяет `UnitEnum`; enum имеет cases и pure/backed semantics | PHP language/runtime. На PHP 8.5.9 проверена допустимость interface chain и pure/backed реализации. |
| Marker соответствует объявленному слою; требуемая cardinality | Только включённый analyzer architecture gate по настроенному context path. Тестируется PHPStan/Psalm fixtures. |
| Транзитивная реализация контракта/alias | Source-level ancestry и name resolution анализатора; не runtime autoload mapper. Общая поддержка проверена, enum alias сценарий требует fixture. |
| Семантика типа действительно domain/config/adapter | Ревью, usage, документация и конкретные consumer examples. Marker самообозначение не доказывает смысл. |
| Domain enum не протащит межконтекстную зависимость | Текущие role gates это не проверяют; нужны отдельные dependency rules/контракт bounded-context. Не заявлять такую гарантию от marker. |
| Значения корректно сериализуются/валидируются/загружаются | Вне предложения и текущих проверок; не включать в обязательные гэпы без отдельного требования. |

### Порядок после решения

1. **Решения модели:** (a) что означает parameter — key/value/operation input; (b) обязаны ли все Application enums иметь role и как классифицируется непредметный Application enum; (c) какой доказанный порт действительно владеет настройкой; (d) существует ли infrastructure non-adapter config; (e) Adapter специализирует ли родителя, а sibling специализации конфликтуют ли; (f) какие contexts и слои обязаны включить gate.
2. **Контракты и mapper:** добавить только одобренные публичные интерфейсы под PSR-4; определить их owner namespaces; добавить enum role family(s); явно схлопывать только принятые пары specialization; сохранить неоднозначность sibling. Проверить layer matrix для Domain/Application/Infrastructure.
3. **Проверки:** fixtures для pure/backed enum, direct/transitive/alias role, parent+child, siblings, unrelated behavioral interface, wrong/missing layer role, DTO use, context boundary. Оба analyzer; тест на неподключенный/неполный scope. Не полагаться на тест, который лишь утверждает class-like traversal.
4. **Документация и потребители:** обновить ADR/OpenSpec, API migration note и downstream примеры; отдельная миграция потребителей только при подтверждённых нарушениях. Текущие новые public interfaces — обратно совместимое добавление на уровне типов, но активация в требующих ровно одну роль consumer contexts может сделать прежние enum объявления ошибками и потребовать маркировку/структурное изменение. Распространять это изменение без consumer inventory нельзя.

Минимальный критерий готовности ограниченного implementation prompt: решения шага 1 записаны, выбран хотя бы один реальный consumer use case на каждую принятую config role, зафиксирована проверяемая layer/cardinality matrix, mapper outcome для specialization однозначен. Без этого реализация должна ограничиться не конфигурационными ролями либо остаться отложенной.

## 8. Открытые вопросы пользователю

1. **Что именно enum должен обозначать под `ConfigParameter`?**
   - ключ/имя настройки (например, фактический ключ, который consumer передаёт при сборке компонента) — рекомендованный термин для обсуждения: `ConfigKey`;
   - допустимое значение одной настройки (нужен реальный параметр и его варианты);
   - параметры конкретной операции (оставить в Command/Query DTO, не считать config);
   - другое: показать concrete consumer contract.
   **Рекомендация:** не фиксировать имя `ConfigParameterInterface`, пока не выбран вариант и не предъявлен consumer.
2. **Есть ли в целевом downstream-коде реальный Application enum вне Domain, например направление сортировки?** Покажите FQCN, port/query/DTO и consumer. Варианты: моделировать как часть операции; добавить узкий Application role; признать предметным только при бизнесовом владении. **Рекомендация:** не вводить общий маркер, проходящий cardinality без проверки смысла.
3. **Какой конкретный параметр требует контракт порта, и кто задаёт/читает его?** Варианты: настройка поведения контракта; аргумент каждой операции; настройка конкретного adapter/composition root. **Рекомендация:** `PortConfig` оставить только если контракт Application реально владеет значением и сохраняет смысл при разных adapters.
4. **Нужен ли реальный non-adapter Infrastructure config enum отдельно от adapter config?** Если да, привести компонент, тип, место конфигурации и consumer. **Рекомендация:** пока отложить базовый Infrastructure и Adapter маркеры.
5. **При подтверждённой специализации согласны с правилом:** Adapter схлопывает совпавшую родительскую Infra role; два sibling child с общим предком остаются ambiguous? **Рекомендация:** да; это согласуется с принятой политикой явного specialization, не generic most-specific precedence.
6. **Должна ли гарантия требовать явного включения architecture gate в потребителе?** Текущая документация говорит opt-in. **Рекомендация:** сохранить opt-in и явно описать CI coverage как ответственность consumer; не выдавать package gate за глобальную проверку.

## Приложение: команды и фактический результат

| Команда / среда | Exit | Результат |
|---|---:|---|
| `rtk composer test:roles` — PHP 8.5.9 | 0 | `Abstraction role tests passed.` |
| `rtk composer test:architecture:analysis` в sandbox | 255 | Не дошёл до assertions: PHPStan worker TCP `127.0.0.1:0` отказан (`EPERM`). |
| Та же команда после разрешённого sandbox escalation | 0 | `Architecture analyzer fixtures passed`; temporary fixture path в системном temp. Подтверждены PHPStan/Psalm valid/invalid diagnostics, ambiguous/missing/wrong-layer/out-of-layer, cache/context scope cases. |
| `rtk composer analyse:phpstan` | 0 | `No errors`. |
| `rtk composer analyse:psalm` | 0 | `No errors found`; 30 info issues при штатной конфигурации. |
| `rtk proxy php -v` | 0 | PHP 8.5.9. |
| Изолированный `php -r` для `interface extends UnitEnum`, inherited interface, pure/backed enum | 0 | Напечатаны cases/values; допустимость подтверждена на PHP 8.5.9. |
| `composer show` | 0 | PHPStan 2.3.1, Psalm 6.20.0, php-cs-fixer 3.95.27; корневой пакет сам в root vendor не установлен как dependency. |

CI `.github/workflows/ci.yml` запускает cs:check, PHPStan, Psalm, role/analyzer/другие тесты и Symfony integration. В этом аудите намеренно не запускались полный CI набор, formatter и automatic fix: для выводов достаточно существующих role + analyzer tests и статических анализаторов. Baseline и architecture suppressions для этого правила не обнаружены; architecture config в root analyzer settings отсутствует. У consumer возможность отключить architecture checks явно документирована.
