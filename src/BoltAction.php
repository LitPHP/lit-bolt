<?php namespace Lit\Bolt;

use Lit\Core\Action;
use Lit\Core\JsonView;

abstract class BoltAction extends Action
{
    /**
     * @var BoltContainer
     */
    protected $container;

    public function __construct(BoltContainer $container)
    {
        $this->container = $container;
    }

    public function json()
    {
        $view = (new JsonView())->setJsonOption(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->attachView($view);
    }

    public function redirect($url, $status = 302)
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus($status);
    }

    protected function getBodyParam($key, $default = null)
    {
        return $this->container->get($this->request->getParsedBody(), $key, $default);
    }

    protected function getQueryParam($key, $default = null)
    {
        return $this->container->get($this->request->getQueryParams(), $key, $default);
    }
}
