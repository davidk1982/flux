<?php
declare(strict_types=1);
namespace FluidTYPO3\Flux\ViewHelpers\Form;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\ViewHelpers\AbstractFormViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Sets an option in the Form instance
 */
class VariableViewHelper extends AbstractFormViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'name',
            'string',
            'Name of the option - valid values and their behaviours depend entirely on the consumer that will ' .
            'handle the Form instance',
            true
        );
        $this->registerArgument('value', 'mixed', 'Value of the option', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        static::getContainerFromRenderingContext($renderingContext)
            ->setVariable($arguments['name'], $arguments['value']);
        return '';
    }
}
