# Receiving nodes

> [!NOTE]
> In some cases we will use an `$id` variable which is an id of the target node.

## Ways to get nodes

- `parent` - To get a Parent Node
- `parents` - To get a Chain of Parent Nodes (till Root)
- `parentByLevel` - To get a Chain of Parent Nodes (till specified level)
- `parentWithTrashed` - To get a Chain of Parent Nodes with Trashed Nodes
- `children` - To get a Collection of direct descendants
- `childrenWithTrashed` - To get a Collection of direct descendants with Trashed Nodes
- `descendants` - To get a Collection of all descendants in Laravel-Relation manner
- `ancestors` - To get a Chain of Parent Nodes (till Root) in Laravel-Relation manner
- `prev` - To get a previous Node
- `next` - To get a next Node
- `prevNodes` - To get a Collection of previous Nodes
- `nextNodes` - To get a Collection of next Nodes
- `siblings` - To get a Collection of all siblings
- `prevSibling` - To get a sibling that is immediately before the node
- `prevSiblings` - To get siblings which are before the node 
- `nextSibling` - To get a sibling that is immediately after the node
- `nextSiblings` - To get siblings which are after the node
- `leaves` - To get a Collection of all leaves
- `leaf` - To get a leaf that is immediately after the node

### Get Parent Node

```php
/** @var \Illuminate\Database\Eloquent\Model $parent */
$parent = $node->parent;
# or
$parent = $node->parent()->first();
```

### Get Parents chains

```php
/** @var \Illuminate\Database\Eloquent\Collection $parents */
$parents = $node->parents();
```

### To get a Chain of Parent Nodes (till specified level)

```php
/** @var \Illuminate\Database\Eloquent\Collection $parents */
$parents = $node->parentByLevel(1);
# or
$parents = $node->parents(1);
```

### To get a Collection of direct descendants

```php
/** @var \Illuminate\Database\Eloquent\Collection $children */
$children = $node->children;
# or
$children = $node->children()->get();
```

### To get a Collection of direct descendants with Trashed Nodes

```php
/** @var \Illuminate\Database\Eloquent\Collection $children */
$children = $node->childrenWithTrashed;
# or
$children = $node->childrenWithTrashed()->get();
# or
$children = $node->children()->withTrashed()->get();
```

### To get a Collection of all descendants in Laravel-Relation manner

```php
/** @var \Illuminate\Database\Eloquent\Collection $children */
$children = $node->descendants;
# or
$children = $node->descendants()->get();
```

### To get a Chain of Parent Nodes (till Root) in Laravel-Relation manner

```php
/** @var \Illuminate\Database\Eloquent\Collection $children */
$children = $node->descendants;
# or
$children = $node->descendants()->get();
```

### Siblings

Siblings are nodes that have same parent.

```php
// Get all siblings of the node
$collection = $node->siblings()->get();

// Get siblings which are before the node
$collection = $node->prevSiblings()->get();

// Get siblings which are after the node
$collection = $node->nextSiblings()->get();

// Get a sibling that is immediately before the node
$prevNode = $node->prevSibling()->first();

// Get a sibling that is immediately after the node
$nextNode = $node->nextSibling()->first();
```

### Direct siblings

```php
$prevNode = $node->prev()->first();
$nextNode = $node->next()->first();
```
