# Laravel Trees

__Contents:__

- [Theory](#information)
- [Installation](#installation)
- [Documentation](#documentation)
  - [Basic](docs/Basic.md)
  - [AdvancedTreeConfig](docs/AdvancedTreeConfig.md)
  - [Migration](docs/Migration.md)
  - [Creating Nodes](docs/CreatingNodes.md)
  - [Managing Nodes](docs/ManagingNodes.md)
  - [Receiving Nodes](docs/ReceivingNodes.md)
  - [Model's Helpers](docs/Helpers.md)
  - [Console](docs/Console.md)
  - [Health And Fixing.md](docs/HealthAndFix.md)

## Information

This package allows to have a single root or multi root tree structure in your Laravel application.

### What are nested sets?

Nested sets or [Nested Set Model](http://en.wikipedia.org/wiki/Nested_set_model) is a way to effectively store
hierarchical data in a relational table. From wikipedia:

> The nested set model is to number the nodes according to a tree traversal,
> which visits each node twice, assigning numbers in the order of visiting, and
> at both visits. This leaves two numbers for each node, which are stored as two
> attributes. Querying becomes inexpensive: hierarchy membership can be tested by
> comparing these numbers. Updating requires renumbering and is therefore expensive.

### Applications

NSM shows good performance when tree is updated rarely. It is tuned to be fast for getting related nodes. It is ideally
suited for building multi-depth menu or categories for shop.

## Requirements

- PHP: 8.2+
- Laravel: ^11.\*|^12.\*

It is highly suggested to use database that supports transactions (like Postgres) to secure a tree from possible
corruption.

## Installation

```shell
composer require lordsimal/laravel-trees
```

## Testing

The testing environment expects a PostgreSQL database to be available via localhost:5432

You can use the provided `docker-compose.yml` file to start a PostgreSQL database via `docker-compose up`.

After that you can run the tests with:

```shell
./vendor/bin/phpunit
# or
composer test
```

## Documentation

The package allows to create multi-root structures: no only-one-root! And allows to move nodes between trees.  
Moreover, it also works with different model's primary key: `int`, `uuid`, `ulid`.

- [Basic](docs/Basic.md)
- [AdvancedTreeConfig](docs/AdvancedTreeConfig.md)
- [Migration](docs/Migration.md)
- [Creating Nodes](docs/CreatingNodes.md)
- [Managing Nodes](docs/ManagingNodes.md)
- [Receiving Nodes](docs/ReceivingNodes.md)
- [Model's Helpers](docs/Helpers.md)
- [Console](docs/Console.md)
- [Health And Fixing.md](docs/HealthAndFix.md)

## Credit where credit is due
This plugin is based on the [Laravel Tree Structure from Eugene Fureev](https://github.com/efureev/laravel-trees)

## License
The plugin is available as open source under the terms of the [MIT License](https://github.com/lordsimal/laravel-trees/blob/main/LICENSE).