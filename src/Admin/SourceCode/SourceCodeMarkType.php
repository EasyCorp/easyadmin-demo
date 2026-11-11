<?php

namespace App\Admin\SourceCode;

enum SourceCodeMarkType
{
    case ClassDeclaration;
    case Method;
    case Lines;
    case Statement;
    case Block;
}
