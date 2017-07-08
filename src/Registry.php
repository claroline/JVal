<?php

/*
 * This file is part of the JVal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JVal;

use JVal\Exception\UnsupportedVersionException;

/**
 * Stores and exposes validation constraints per version.
 *
 * Note: the registry is currently locked on the latest version of the specification;
 *       as changes introduced by a new version might impact other areas than the
 *       constraint validation (i.e. reference resolution) it's probably wise to
 *       keep it that way and completely drop support for schemas mixing several
 *       versions of the specification.
 */
class Registry
{
    const VERSION_CURRENT = 'http://json-schema.org/schema#';
    const VERSION_DRAFT_6 = 'http://json-schema.org/draft-06/schema#';

    private static $constraintNames = [
        'Maximum',
        'Minimum',
        'MaxLength',
        'MinLength',
        'Pattern',
        'Items',
        'MaxItems',
        'MinItems',
        'UniqueItems',
        'Required',
        'Properties',
        'Dependencies',
        'Enum',
        'Type',
        'Format',
        'MultipleOf',
        'MinProperties',
        'MaxProperties',
        'AllOf',
        'AnyOf',
        'OneOf',
        'Not',
    ];

    /**
     * @var Constraint[][]
     */
    private $constraints = [];

    /**
     * @var Constraint[][]
     */
    private $constraintsForTypeCache = [];

    /**
     * @var array
     */
    private $keywordsCache = [];

    /**
     * Returns the constraints associated with a given JSON Schema version.
     *
     * @param string $version
     *
     * @return Constraint[]
     *
     * @throws UnsupportedVersionException if the version is not supported
     */
    public function getConstraints($version)
    {
        if (!isset($this->constraints[$version])) {
            $this->constraints[$version] = $this->createConstraints($version);
        }

        return $this->constraints[$version];
    }

    /**
     * Returns the constraints associated with a given JSON Schema version
     * supporting a given primitive type.
     *
     * @param string $version
     * @param string $type
     *
     * @return Constraint[]
     *
     * @throws UnsupportedVersionException if the version is not supported
     */
    public function getConstraintsForType($version, $type)
    {
        $cache = & $this->constraintsForTypeCache[$version.$type];

        if ($cache === null) {
            $cache = [];

            foreach ($this->getConstraints($version) as $constraint) {
                if ($constraint->supports($type)) {
                    $cache[] = $constraint;
                }
            }
        }

        return $cache;
    }

    /**
     * Returns whether a keyword is supported in a given JSON Schema version.
     *
     * @param string $version
     * @param string $keyword
     *
     * @return bool
     *
     */
    public function hasKeyword($version, $keyword)
    {
        $cache = & $this->keywordsCache[$version];

        if ($cache === null) {
            $cache = [];

            foreach ($this->getConstraints($version) as $constraint) {
                foreach ($constraint->keywords() as $constraintKeyword) {
                    $cache[$constraintKeyword] = true;
                }
            }
        }

        return isset($cache[$keyword]);
    }

    /**
     * Loads the constraints associated with a given JSON Schema version.
     *
     * @param string $version
     *
     * @return Constraint[]
     *
     * @throws UnsupportedVersionException if the version is not supported
     */
    protected function createConstraints($version)
    {
        switch ($version) {
            case self::VERSION_CURRENT:
            case self::VERSION_DRAFT_6:
                return $this->createBuiltInConstraints(self::$constraintNames);
            default:
                throw new UnsupportedVersionException(
                    "Schema version '{$version}' not supported"
                );
        }
    }

    private function createBuiltInConstraints(array $constraintNames)
    {
        return array_map(function ($name) {
            $class = "JVal\\Constraint\\{$name}Constraint";

            return new $class();
        }, $constraintNames);
    }
}
