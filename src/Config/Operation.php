<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Config;

enum Operation
{
    case MakeRoot;
    case PrependTo;
    case AppendTo;
    case InsertBefore;
    case InsertAfter;
    case DeleteAll;
    case RestoreSelfOnly;
}
