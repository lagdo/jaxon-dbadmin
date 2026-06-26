<?php

namespace Lagdo\DbAdmin\Support\Service\Query;

enum QueryStreamContext
{
    case NONE;

    case MULTILINE_COMMENT;

    case MULTILINE_STRING;

    case MULTILINE_FUNCTION;
}
