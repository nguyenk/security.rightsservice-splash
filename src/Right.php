<?php

namespace Mouf\Security;

use Interop\Container\ContainerInterface;
use Mouf\Security\RightsService\RightsServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The @Right filter should be used to check whether a user has
 * a certain right or not.
 *
 * It will try to do so by querying the "rightsService" instance, that should
 * be an instance of the "MoufRightService" class (or a class extending the RightsServiceInterface).
 *
 * This filter requires at least one parameter: "name"
 *
 * So @Right(name="Admin") will require the Admin right to be logged.
 * Otherwise, the user is redirected to an error page.
 *
 * You can pass an additional parameter to overide the name of the instance.
 * For instance: @Right(name="Admin",middlewareName="myRightMiddleware") will
 * verify that the user has the correct right using the "myRightService" instance.
 *
 *
 * @Annotation
 * @Attributes({
 *   @Attribute("middlewareName", type = "string"),
 * })
 */
class Right implements RightAnnotationInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * The value passed to the filter.
     */
    protected $middlewareName;

    /**
     * Logged constructor.
     *
     * @param array $values
     * @throws \BadMethodCallException
     */
    public function __construct(array $values)
    {
        $this->middlewareName = $values['middlewareName'] ?? ForbiddenMiddleware::class;
        if (!isset($values['value']) && !isset($values['name'])) {
            throw new \BadMethodCallException('The @Right annotation must be passed a right name. For instance: "@Right(\'my_right\')"');
        }
        $this->name = $values['value'] ?? $values['name'];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     * @param ContainerInterface     $container
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next, ContainerInterface $container)
    {
        $middlewareName = $container->get($this->middlewareName);

        $response = $middlewareName($request, $response, $next, $container);

        return $response;
    }

    /**
     *
     * @param RightsServiceInterface $rightsService
     * @return bool
     */
    public function isAllowed(RightsServiceInterface $rightsService) : bool
    {
        return $rightsService->isAllowed($this->name);
    }
}
