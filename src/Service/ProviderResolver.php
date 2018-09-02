<?php


namespace App\Service;


use App\Exception\NonExistentServiceException;
use App\Provider\Provider;
use App\Provider\ZtmGdanskProvider;

class ProviderResolver
{
    private const PROVIDER = [
        'gdansk' => ZtmGdanskProvider::class
    ];

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * ProviderResolver constructor.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**\
     * @param string $name
     *
     * @return \App\Provider\Provider
     * @throws \App\Exception\NonExistentServiceException
     */
    public function resolve(string $name): Provider
    {
        if (!array_key_exists($name, static::PROVIDER)) {
            throw new NonExistentServiceException();
        }

        return $this->container->get(static::PROVIDER[$name]);
    }
}