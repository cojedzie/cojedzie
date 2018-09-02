<?php


namespace App\Service;


use App\Exception\NonExistentServiceException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\StopRepository;
use const Kadet\Functional\_;
use function Kadet\Functional\any;
use function Kadet\Functional\curry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepositoryParameterConverter implements ParamConverterInterface
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
        if (!$request->attributes->has('provider')) {
            return false;
        }

        $provider = $request->attributes->get('provider');

        try {
            $provider = $this->resolver->resolve($provider);
            $class    = $configuration->getClass();
            switch (true) {
                case is_a($class, StopRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getStopRepository());
                    break;

                case is_a($class, LineRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getLineRepository());
                    break;

                case is_a($class, DepartureRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getDepartureRepository());
                    break;

                default:
                    return false;
            }

            return true;
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$provider'.", $exception);
        }
    }

    public function supports(ParamConverter $configuration)
    {
        $instance = curry('is_a', 3)(_, _, true);

        return any(
            $instance(StopRepository::class),
            $instance(LineRepository::class),
            $instance(DepartureRepository::class)
        )($configuration->getClass());
    }
}