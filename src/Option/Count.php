<?php

namespace Flat3\OData\Option;

use Flat3\OData\Option;

/**
 * Class Count
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptioncount
 */
class Count extends Option\Boolean
{
    public const param = 'count';
}
