<?php

namespace Flat3\OData\Option;

use Flat3\OData\EntitySet;
use Flat3\OData\Expression\Parser\Filter as Parser;
use Flat3\OData\Option;

/**
 * Class Filter
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionfilter
 */
class Filter extends Option
{
    public const param = 'filter';

    public function apply_query(EntitySet $query, array $validLiterals = []): void
    {
        if (!$this->hasValue()) {
            return;
        }

        $parser = new Parser($query);

        foreach ($validLiterals as $validLiteral) {
            $parser->addValidLiteral($validLiteral);
        }

        $tree = $parser->generateTree($this->getValue());
        $tree->compute();
    }
}
