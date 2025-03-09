# Health-ing And Fixing

## Checking consistency

You can check whether a tree is broken (i.e. has some structural errors):

```php
$bool = Category::isBroken();
```

It is possible to get error statistics:

```php
$data = Category::countErrors();
```

It returns an array with following keys:

- `oddness` - the number of nodes that have wrong set of `lft` and `rgt` values
- `duplicates` - the number of nodes that have same `lft` or `rgt` values
- `wrong_parent` - the number of nodes that have invalid `parent_id` value that doesn't correspond to `lft` and `rgt`
  values
- `missing_parent` - the number of nodes that have `parent_id` pointing to node that doesn't exists

## Fixing tree

For single tree:

```php
Category::fixTree();
```

For multi tree:

```php
Category::fixMultiTree();
```

