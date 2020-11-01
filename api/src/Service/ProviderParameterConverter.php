<?php


namespace App\Service;


use App\Exception\NonExistentServiceException;
use App\Provider\Provider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProviderParameterConverter implements ParamConverterInterface
{
    private $resolver;

    /**
     * ProviderParameterConverter constructor.
     *
     * @param $resolver
     */
    public function __construct(ProviderResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $provider = $request->get('provider');

        try {
            $request->attributes->set('provider', $this->resolver->resolve($provider));
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$provider'.", $exception);
        }
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getName() === 'provider' && is_a($configuration->getClass(), Provider::class, true);
    }
}