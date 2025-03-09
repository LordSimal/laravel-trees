# Managing nodes

## Move Nodes

### Move a Node up in self parent scope

```php
$node->up();
```

### Move a Node down in self parent scope

```php
$node->down();
```

## Delete Nodes

**Basic**

Remove a target Node only. All it's descendants will be moved to target-node's parent (default behavior).

```php
$node->delete();
```

> [!NOTE]
> To change strategy about handle children, you should set up Tree Builder's prop `childrenHandlerOnDelete`. 
> By default, it uses `\LordSimal\LaravelTrees\Strategy\MoveChildrenToParent` handler.

**WithChildren**

Delete a target node with all it's descendants (include deep-included).

```php
$node->deleteWithChildren();
```

> [!NOTE]
> It's a default behavior. To change strategy about handle children, you should set up Tree Builder's prop `deleterWithChildren`. 
> By default, it uses `\LordSimal\LaravelTrees\Strategy\DeleteWithChildren` handler.

> [!WARNING]
> All children will be deleted via a SQL Query (not through Model)!

> [!CAUTION]
> Nodes are required to be deleted as models! **DO NOT** try to delete them using a query like so:

```php
Category::where('id', '=', $id)->delete();
```

**This will break the tree!**

## Delete SoftDeletable Models

The Tree works normally with `SoftDelete` trait. 

