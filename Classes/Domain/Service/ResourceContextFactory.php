<?php
namespace Neos\ContentRepository\ResourceAdapter\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository.ResourceAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Exception\InvalidNodeContextException;

/**
 * ResourceContextFactory which ensures contexts stay unique. Make sure to
 * get ContextFactoryInterface injected instead of this class.
 *
 * See \TYPO3\TYPO3CR\Domain\Service\ContextFactory->build for detailed
 * explanations about the usage.
 *
 * @Flow\Scope("singleton")
 */
class ResourceContextFactory extends ContextFactory
{
    /**
     * The context implementation this factory will create
     *
     * @var string
     */
    protected $contextImplementation = 'Neos\ContentRepository\ResourceAdapter\Domain\Service\ResourceContext';

    /**
     * Creates the actual Context instance.
     * This needs to be overridden if the Builder is extended.
     *
     * @param array $contextProperties
     * @return ResourceContext
     */
    protected function buildContextInstance(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);

        return new ResourceContext(
            $contextProperties['workspaceName'],
            $contextProperties['currentDateTime'],
            $contextProperties['dimensions'],
            $contextProperties['targetDimensions'],
            $contextProperties['invisibleContentShown'],
            $contextProperties['removedContentShown'],
            $contextProperties['inaccessibleContentShown']
        );
    }

    /**
     * Merges the given context properties with sane defaults for the context implementation.
     *
     * @param array $contextProperties
     * @return array
     */
    protected function mergeContextPropertiesWithDefaults(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);

        $defaultContextProperties = [
            'workspaceName' => 'live',
            'currentDateTime' => $this->now,
            'dimensions' => [],
            'targetDimensions' => [],
            'invisibleContentShown' => false,
            'removedContentShown' => false,
            'inaccessibleContentShown' => false
        ];

        $mergedProperties = Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, true);

        $this->mergeDimensionValues($contextProperties, $mergedProperties);
        $this->mergeTargetDimensionContextProperties($contextProperties, $mergedProperties, $defaultContextProperties);

        return $mergedProperties;
    }

    /**
     * This creates the actual identifier and needs to be overridden by builders extending this.
     *
     * @param array $contextProperties
     * @return string
     */
    protected function getIdentifierSource(array $contextProperties)
    {
        ksort($contextProperties);
        $identifierSource = $this->contextImplementation;
        foreach ($contextProperties as $propertyName => $propertyValue) {
            if ($propertyName === 'dimensions') {
                $stringParts = [];
                foreach ($propertyValue as $dimensionName => $dimensionValues) {
                    $stringParts[] = $dimensionName . '=' . implode(',', $dimensionValues);
                }
                $stringValue = implode('&', $stringParts);
            } elseif ($propertyName === 'targetDimensions') {
                $stringParts = [];
                foreach ($propertyValue as $dimensionName => $dimensionValue) {
                    $stringParts[] = $dimensionName . '=' . $dimensionValue;
                }
                $stringValue = implode('&', $stringParts);
            } elseif ($propertyValue instanceof \DateTimeInterface) {
                $stringValue = $propertyValue->getTimestamp();
            } else {
                $stringValue = (string)$propertyValue;
            }
            $identifierSource .= ':' . $stringValue;
        }

        return $identifierSource;
    }
}
