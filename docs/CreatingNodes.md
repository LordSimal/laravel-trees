# Creating nodes

A Node is usual a Laravel Model. First Node is named `Root Node` (or `Root`). The Root node contains other nodes inside.

## Creating root nodes

**`Single Tree Mode` and `Multi Tree Mode`**

Root nodes needs be specified as such.

```php
new Category($attributes)->makeRoot()->save(); 
// or
new Category($attributes)->saveAsRoot();
```

## Creating sub nodes

Non-Root Nodes must be appended into another Node.

There are several ways to add/create new Non-Nodes to other nodes:

- `PrependTo`: Adds a node inside another node. The Node is inserted BEFORE other children of the parent node.
- `AppendTo`: Adds a node inside another node. The Node is inserted AFTER other children of the parent node.
- `InsertBefore`: Adds child-node into same parent node. The Node is inserted BEFORE target node.
- `InsertAfter`: Adds child-node into same parent node. The Node is inserted AFTER target node.

Examples:

```php
$node->prependTo($parent)->save();
$node->appendTo($parent)->save();

$node->insertBefore($parent)->save();
$node->insertAfter($parent)->save();
```
