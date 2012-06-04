<?php

namespace Havvg\Bundle\DRYBundle\Tests\DependencyInjection\Compiler;

use Havvg\Bundle\DRYBundle\Tests\AbstractTest;
use Havvg\Bundle\DRYBundle\Tests\Fixtures\TaggedCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers Havvg\Bundle\DRYBundle\DependencyInjection\Compiler\AbstractTaggedCompilerPass
 */
class AbstractTaggedCompilerPassTest extends AbstractTest
{
    public function testWithoutTargetService()
    {
        $builder = $this->getBuilder();
        $builder->expects($this->once())
            ->method('hasDefinition')
            ->will($this->returnValue(false))
        ;
        $builder
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $compilerPass = new TaggedCompilerPass();
        $compilerPass->process($builder);
    }

    public function testWithoutTaggedServices()
    {
        $builder = $this->getBuilder();
        $builder
            ->expects($this->once())
            ->method('hasDefinition')
            ->will($this->returnValue(true))
        ;
        $builder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue(array()))
        ;

        $compilerPass = new TaggedCompilerPass();
        $compilerPass->process($builder);
    }

    public function testWithTaggedServices()
    {
        $targetService = new Definition();
        $targetService->setClass('Havvg\Bundle\DRYBundle\Tests\Fixtures\TargetService');

        $provider = $this->getMock('Havvg\Bundle\DRYBundle\Tests\Fixtures\TargetService');
        $providerService = new Definition();
        $providerService->setClass(get_class($provider));
        $providerService->addTag('acme.service_tag');

        $other = $this->getMock('Havvg\Bundle\DRYBundle\Tests\Fixtures\TargetService');
        $otherService = new Definition();
        $otherService->setClass(get_class($other));
        $otherService->addTag('acme.different_tag');

        $builder = new ContainerBuilder();
        $builder->addDefinitions(array(
            'acme.target_service' => $targetService,
            'acme.provider_service' => $providerService,
        ));

        $builder->addCompilerPass(new TaggedCompilerPass());
        $builder->compile();

        $this->assertNotEmpty($builder->getServiceIds(),
            'The services have been injected.');
        $this->assertNotEmpty($builder->get('acme.target_service'),
            'The target service has been injected.');
        $this->assertNotEmpty($builder->get('acme.provider_service'),
            'The provider service has been injected.');

        /*
         * Schema:
         *
         * [0] The list of methods.
         *   [0] The name of the method to call.
         *   [1] The arguments to pass into the method call.
         *     [0] First argument to pass into the method call.
         *     ...
         */
        $targetMethodCalls = $builder->getDefinition('acme.target_service')->getMethodCalls();
        $this->assertNotEmpty($targetMethodCalls,
            'The target service got method calls added.');
        $this->assertEquals('addService', $targetMethodCalls[0][0],
            'The target service got a provider added.');
        $this->assertEquals('acme.provider_service', $targetMethodCalls[0][1][0],
            'The target service got the correct provider added.');
        $this->assertCount(1, $targetMethodCalls,
            'The other service has not been added.');
    }
}
