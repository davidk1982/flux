<?php
declare(strict_types=1);
namespace FluidTYPO3\Flux\Content;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Content\TypeDefinition\ContentTypeDefinitionInterface;
use FluidTYPO3\Flux\Content\TypeDefinition\FluidRenderingContentTypeDefinitionInterface;
use FluidTYPO3\Flux\Integration\ViewBuilder;
use FluidTYPO3\Flux\Service\TemplateValidationService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Parser\Sequencer;
use TYPO3Fluid\Fluid\Core\Parser\Source;

/**
 * TCA user feedback: Content Type Validation
 *
 * Provides feedback with validation of the content
 * type being edited, such as validating the Fluid
 * template, the CType value, extension context and
 * other data.
 *
 * Rendered as a custom TCA field.
 */
class ContentTypeValidator
{
    public function validateContentTypeRecord(array $parameters): string
    {
        /** @var ViewBuilder $viewBuilder */
        $viewBuilder = GeneralUtility::makeInstance(ViewBuilder::class);
        $view = $viewBuilder->buildTemplateView('FluidTYPO3.Flux', 'Content', 'validation');

        $record = $parameters['row'];
        $recordIsNew = strncmp((string)$record['uid'], 'NEW', 3) === 0;

        $view->assign('recordIsNew', $recordIsNew);

        if ($recordIsNew) {
            return $view->render();
        }

        /** @var ContentTypeManager $contentTypeManager */
        $contentTypeManager = GeneralUtility::makeInstance(ContentTypeManager::class);
        $contentType = $contentTypeManager->determineContentTypeForTypeString($record['content_type']);
        if (!$contentType) {
            $view->assign('recordIsNew', true);
            return $view->render();
        }

        $usesTemplateFile = true;
        if ($contentType instanceof FluidRenderingContentTypeDefinitionInterface) {
            $usesTemplateFile = $contentType->isUsingTemplateFile()
                && file_exists($this->resolveAbsolutePathForFilename($contentType->getTemplatePathAndFilename()));
        }

        $view->assignMultiple([
            'recordIsNew' => $recordIsNew,
            'contentType' => $contentType,
            'record' => $record,
            'usages' => $this->countUsages($contentType),
            'validation' => [
                'extensionInstalled' => $this->validateContextExtensionIsInstalled($contentType),
                'extensionMatched' => $this->validateContextMatchesSignature($contentType),
                'templateSource' => $this->getTemplateValidationService()->validateContentDefinition($contentType),
                'templateFile' => $usesTemplateFile,
                'icon' => !empty($record['icon'])
                    && file_exists($this->resolveAbsolutePathForFilename($record['icon'])),
            ],
        ]);

        return $view->render();
    }

    protected function validateContextMatchesSignature(ContentTypeDefinitionInterface $definition): bool
    {
        $parts = explode('_', $definition->getContentTypeName());
        return str_replace(
            '_',
            '',
            ExtensionNamingUtility::getExtensionKey($definition->getExtensionIdentity())
        ) === reset($parts);
    }

    protected function validateContextExtensionIsInstalled(ContentTypeDefinitionInterface $definition): bool
    {
        return ExtensionManagementUtility::isLoaded(
            ExtensionNamingUtility::getExtensionKey($definition->getExtensionIdentity())
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function countUsages(ContentTypeDefinitionInterface $definition): int
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter($definition->getContentTypeName(), \PDO::PARAM_STR)
                )
            );
        return (integer) $queryBuilder->execute()->rowCount();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function resolveAbsolutePathForFilename(string $filename): string
    {
        return GeneralUtility::getFileAbsFileName($filename);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateValidationService(): TemplateValidationService
    {
        /** @var TemplateValidationService $templateValidationService */
        $templateValidationService = GeneralUtility::makeInstance(TemplateValidationService::class);
        return $templateValidationService;
    }
}
