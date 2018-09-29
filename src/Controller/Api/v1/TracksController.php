<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Provider\TrackRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function App\Functions\encapsulate;

/**
 * @Route("/tracks")
 */
class TracksController extends Controller
{
    /**
     * @Route("/")
     */
    public function index(Request $request, TrackRepository $repository)
    {
        switch (true) {
            case $request->query->has('stop'):
                return $this->byStop($request, $repository);
            case $request->query->has('line'):
                return $this->byLine($request, $repository);
            case $request->query->has('id'):
                return $this->byId($request, $repository);
            default:
                throw new BadRequestHttpException(sprintf('At least one parameter of %s must be set.', implode(', ', ['stop', 'line', 'id'])));
        }
    }

    private function byId(Request $request, TrackRepository $repository)
    {
        $id = encapsulate($request->query->get('id'));

        return $this->json($repository->getManyById($id));
    }

    private function byStop(Request $request, TrackRepository $repository)
    {
        $stop = encapsulate($request->query->get('stop'));

        return $this->json($repository->getByStop($stop));
    }

    private function byLine(Request $request, TrackRepository $repository)
    {
        $line = encapsulate($request->query->get('line'));

        return $this->json($repository->getByLine($line));
    }
}