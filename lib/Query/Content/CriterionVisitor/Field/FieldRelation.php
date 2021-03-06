<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\Query\Content\CriterionVisitor\Field;

use EzSystems\EzPlatformSolrSearchEngine\Query\CriterionVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Query\Content\CriterionVisitor\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Visits the FieldRelation criterion.
 */
class FieldRelation extends Field
{
    /**
     * Check if visitor is applicable to current criterion.
     *
     * @param Criterion $criterion
     *
     * @return bool
     */
    public function canVisit(Criterion $criterion)
    {
        return
            $criterion instanceof Criterion\FieldRelation &&
            (($criterion->operator ?: Operator::IN) === Operator::IN ||
                $criterion->operator === Operator::CONTAINS);
    }

    /**
     * Map field value to a proper Solr representation.
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException If no searchable fields are found for the given criterion target.
     *
     * @param Criterion $criterion
     * @param CriterionVisitor $subVisitor
     *
     * @return string
     */
    public function visit(Criterion $criterion, CriterionVisitor $subVisitor = null)
    {
        $fieldNames = $this->getFieldNames($criterion, $criterion->target);

        if (empty($fieldNames)) {
            throw new InvalidArgumentException(
                '$criterion->target',
                "No searchable fields found for the given criterion target '{$criterion->target}'."
            );
        }

        $criterion->value = (array)$criterion->value;

        $queries = array();
        foreach ($criterion->value as $value) {
            $preparedValue = $this->escapeQuote($this->toString($value), true);

            foreach ($fieldNames as $name) {
                $queries[] = $name . ':"' . $preparedValue . '"';
            }
        }

        switch ($criterion->operator) {
            case Operator::CONTAINS:
                $op = ' AND ';
                break;
            case Operator::IN:
            default:
                $op = ' OR ';
        }

        return '(' . implode($op, $queries) . ')';
    }
}
