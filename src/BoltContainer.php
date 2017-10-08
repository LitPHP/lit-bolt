<?php namespace Lit\Bolt;

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Lit\Air\Injection\SetterInjector;
use Lit\Air\Psr\Container;
use Lit\Core\Interfaces\IRouter;
use Lit\Core\Interfaces\IStubResolver;
use Lit\Nexus\Void\VoidSingleValue;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @property EventDispatcherInterface $events
 * @property BoltStubResolver $stubResolver
 * @property IRouter $router
 * @property BoltApp $app
 * @property PropertyAccessor $accessor
 */
class BoltContainer extends Container
{
    public function __construct(?array $config = null)
    {
        parent::__construct(
            [
                Container::KEY_INJECTORS => ['value', [new SetterInjector()]],
                BoltContainer::class => $this,

                'stubResolver' => ['alias', IStubResolver::class],
                IStubResolver::class => ['autowire', BoltStubResolver::class],//lit-core

                DataGenerator::class => ['autowire', DataGenerator\GroupCountBased::class],//fast-route
                RouteParser::class => ['autowire', RouteParser\Std::class],//fast-route

                'accessor' => ['alias', PropertyAccessor::class],
                PropertyAccessor::class => [
                    'autowire',
                    null,
                    [
                        false,// $magicCall
                        true,// $throwExceptionOnInvalidIndex
                    ]
                ],

                IRouter::class => ['alias', BoltRouter::class],//lit-core
                'router' => ['alias', BoltRouter::class],
                BoltRouter::class => [
                    'autowire',
                    null,
                    [
                        'cache' => new VoidSingleValue(),
                        'routeDefinition' => self::alias(BoltRouteDefinition::class),
                        'dispatcherClass' => Dispatcher\GroupCountBased::class,
                        'notFound' => new class implements MiddlewareInterface
                        {
                            public function process(ServerRequestInterface $request, DelegateInterface $delegate)
                            {
                                return new EmptyResponse(404);
                            }

                        },
                    ]
                ],

                'events' => ['autowire', EventDispatcher::class],
            ] +
            ($config ?: [])
        );
    }

    function __get($name)
    {
        return $this->get($name);
    }

    function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * grab $target's $key value, while not available, return $default instead
     * delegate to symfony/property-access
     *
     * @param mixed $target array or object target
     * @param string $key support symfony/property-access key format
     * @param null $default default value while $key is not available
     * @return mixed
     */
    public function access($target, $key, $default = null)
    {
        if (!$this->accessor->isReadable($target, $key)) {
            return $default;
        }

        return $this->accessor->getValue($target, $key);
    }
}
