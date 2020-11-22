<?php


namespace App\Service;


use App\Exception\NonExistentServiceException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\TripRepository;
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

                case is_a($class, MessageRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getMessageRepository());
                    break;

                case is_a($class, TrackRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getTrackRepository());
                    break;

                case is_a($class, TripRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getTripRepository());
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
        $supports = any(array_map(curry('is_a', 3)(_, _, true), [
            StopRepository::class,
            LineRepository::class,
            DepartureRepository::class,
            MessageRepository::class,
            TrackRepository::class,
            TripRepository::class,
        ]));

        return $supports($configuration->getClass());
    }
}