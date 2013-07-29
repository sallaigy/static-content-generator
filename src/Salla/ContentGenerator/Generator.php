<?php

namespace Salla\ContentGenerator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\HttpKernel\HttpKernelInterface;

use Salla\ContentGenerator\DataSource\DataSourceInterface;

/**
 * Generator 
 * 
 * @author Gyula Sallai <salla016@gmail.com>
 */
class Generator
{

    protected $routes = array();

    protected $kernel;

    protected $urlGenerator;

    protected $routeCollection; 

    /**
     * Constructor
     * 
     * @param HttpKernelInterface   $kernel          The application kernel to use
     * @param UrlGeneratorInterface $urlGenerator    The url generator service to use
     * @param RouteCollection       $routeCollection The route collection of the application router
     */
    public function __construct(HttpKernelInterface $kernel, UrlGeneratorInterface $urlGenerator, RouteCollection $routeCollection)
    {
        $this->kernel = $kernel;
        $this->urlGenerator = $urlGenerator;
        $this->routeCollection = $routeCollection;
    }

    /**
     * Add a single route to the generator
     * 
     * @param string $name       The name of the route
     * @param mixed $dataSource  The data source to use with this route
     */
    public function addRoute($name, $dataSource)
    {
        $this->routes[$name] = $dataSource;
    }
 
    /**
     * Add multiple routes with the same data source
     * 
     * @param array  $routes     An array of route names
     * @param mixed  $dataSource The data source to use with the given routes
     */
    public function addRoutes(array $routes, $dataSource)
    {
        foreach ($routes as $name) {
            $this->addRoute($name, $dataSource);
        }
    }

    /**
     * Generate and dump the static files into the given directory
     * 
     * @param  string $directory The path to the directory
     * @param  string $suffix    The suffix (extension) of the generated files
     */
    public function dump($directory, $suffix = '.html')
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException('Invalid directory');
        }

        $directory = realpath($directory);

        $responses = $this->generate();

        foreach ($responses as $url => $content) {
            $pos = stripos($url, DIRECTORY_SEPARATOR);
            $dir = substr($url, $pos, strlen($url) - $pos);

            $path = $directory . DIRECTORY_SEPARATOR . $dir;

            if (!is_dir($path) && $dir !== '') {
                if (false === mkdir($path, 0777, true)) {
                    throw new \RuntimeException(sprintf("Failed to create directory '%s'", $path));
                }
            }

            $file = $directory . DIRECTORY_SEPARATOR . $url . $suffix;

            if (false === file_put_contents($file, $content)) {
                throw new \RuntimeException(sprintf("Failed to write file '%s'", $file));
            }
        }
    }

    /**
     * Generate all responses and return them as an array
     * 
     * @return array
     */
    public function generate()
    {
        $responses = array();

        foreach ($this->routes as $name => $dataSource) {
            $responses = array_merge($responses, $this->generateForRoute($name, $dataSource));
        }

        return $responses;
    }

    /**
     * Generate for a single route with the given data source
     * 
     * @param  string $name       The name of the route
     * @param  mixed $dataSource  The data source to use
     * 
     * @return array
     */
    public function generateForRoute($name, $dataSource = array(array()))
    {
        if (null === $route = $this->routeCollection->get($name)) {
            throw new \InvalidArgumentException(sprintf("Route '%s' does not exist.", $name));
        }

        $compiledRoute = $route->compile();

        $variables = $compiledRoute->getVariables();

        if ($dataSource instanceof DataSourceInterface) {
            $dataSource = $dataSource->getData($variables);
        } else if ($dataSource instanceof \Closure) {
            $dataSource = $dataSource($variables);
        }

        if (!is_array($dataSource)) {
            throw new \RuntimeException("Data sources should produce/be arrays.");
        }

        $responses = array();
        $variables = array_flip($variables);

        foreach ($dataSource as $data) {
            $data = array_intersect_key($data, $variables);

            $url = $this->urlGenerator->generate($name, $data);
            $request = Request::create($url, 'GET');

            $response = $this->kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, true);

            if ($response->isSuccessful()) {
                // We only add a response to the collection if it is successful 
                $responses[$url] = $response->getContent();
            }
        }

        return $responses;
    }

}