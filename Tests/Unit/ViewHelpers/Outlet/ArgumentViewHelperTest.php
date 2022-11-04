<?php
namespace FluidTYPO3\Flux\Tests\Unit\ViewHelpers\Outlet;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Outlet\OutletArgument;
use FluidTYPO3\Flux\Tests\Fixtures\Classes\AccessibleArgumentViewHelper;
use FluidTYPO3\Flux\Tests\Unit\ViewHelpers\AbstractViewHelperTestCase;
use FluidTYPO3\Flux\ViewHelpers\AbstractFormViewHelper;
use FluidTYPO3\Flux\ViewHelpers\Outlet\ArgumentViewHelper;
use FluidTYPO3\Flux\ViewHelpers\Outlet\ValidateViewHelper;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * ArgumentViewHelperTest
 */
class ArgumentViewHelperTest extends AbstractViewHelperTestCase
{
    private ?OutletArgument $argument;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->argument = $this->getMockBuilder(OutletArgument::class)
            ->setMethods(['addValidator'])
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];

        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMockForAbstractClass();
        $objectManager->method('get')->willReturn($this->argument);

        AccessibleArgumentViewHelper::setObjectManager($objectManager);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        AccessibleArgumentViewHelper::setObjectManager(null);
    }

    /**
     * @test
     */
    public function testAddsArgumentToOutlet()
    {
        $outlet = $this->getMockBuilder(Outlet::class)->setMethods(['addArgument'])->getMock();
        $outlet->expects($this->once())->method('addArgument')->with($this->anything());
        $form = $this->getMockBuilder(Form::class)->setMethods(['getOutlet'])->getMock();
        $form->expects($this->once())->method('getOutlet')->willReturn($outlet);
        $this->viewHelperVariableContainer->add(AbstractFormViewHelper::SCOPE, 'form', $form);

        AccessibleArgumentViewHelper::renderStatic(['name' => 'test', 'type' => 'string'], function () {
            return null;
        }, $this->renderingContext);
    }

    public function testAddsValidatorsFromChildNodes()
    {
        $form = Form::create();

        $viewHelperVariableContainer = $this->viewHelperVariableContainer;
        $viewHelperVariableContainer->addOrUpdate(
            AbstractFormViewHelper::SCOPE,
            AbstractFormViewHelper::SCOPE_VARIABLE_FORM,
            $form
        );

        $arguments = ['name' => 'test', 'type' => 'string'];

        $this->argument->expects(self::once())->method('addValidator')->with('NotEmpty', []);

        ArgumentViewHelper::renderStatic($arguments, function () use ($viewHelperVariableContainer) {
            $viewHelperVariableContainer->addOrUpdate(
                ValidateViewHelper::class,
                'validators',
                [
                    [
                        'type' => 'NotEmpty',
                        'options' => [],
                    ]
                ]
            );
        }, $this->renderingContext);
    }
}
