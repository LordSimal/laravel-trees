# Console

Build and draw table from the Tree:

```php
Table::fromModel($rootNode)->draw();
```

```php
$collection = Category::all();

Table::fromTree($collection->toTree())
    ->hideLevel()
    ->setExtraColumns(
        [
            'title'                         => 'Label',
            (string)$root->leftAttribute()  => 'Left',
            (string)$root->rightAttribute() => 'Right',
        ]
    )
    ->draw($output);
```

and looks something like this:

```
+-------+------------------+-------------+
| Level | ID               | Label       |
+-------+------------------+-------------+
| 0     |  + 1             | root node   |
| 1     |      + 2         | node 1      |
| 2     |          + 3     | child 2.1   |
| 3     |              - 4 | child 2.1.1 |
| 2     |          - 5     | child 2.2   |
+-------+--- Total nodes: 5 -------------+
```