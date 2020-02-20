<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Line;
use App\Model\Stop;
use App\Model\Track;
use App\Modifier\IdFilter;
use App\Modifier\RelatedFilter;
use App\Provider\TrackRepository;
use App\Service\IterableUtils;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function App\Functions\encapsulate;

/**
 * @Route("/tracks")
 * @SWG\Tag(name="Tracks")
 */
class TracksController extends Controller
{
    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns all tracks for specific provider, e.g. ZTM Gdańsk.",
     * )
     * @Route("/", methods={"GET"})
     */
    public function index(Request $request, TrackRepository $repository)
    {
        $modifiers = $this->getModifiersFromRequest($request);

        return $this->json($repository->all(...$modifiers));
    }

    /**
     * @Route("/stops", methods={"GET"})
     * @Route("/{track}/stops", methods={"GET"})
     */
    public function stops(Request $request, TrackRepository $repository)
    {
        $modifiers = $this->getStopsModifiersFromRequest($request);

        return $this->json($repository->stops(...$modifiers));
    }

    private function getModifiersFromRequest(Request $request)
    {
        if ($request->query->has('stop')) {
            $stop = $request->query->get('stop');
            $stop = Stop::reference($stop);

            yield new RelatedFilter($stop);
        }

        if ($request->query->has('line')) {
            $line = $request->query->get('line');
            $line = Line::reference($line);

            yield new RelatedFilter($line);
        }

        if ($request->query->has('id')) {
            $id = encapsulate($request->query->get('id'));

            yield new IdFilter($id);
        }
    }

    private function getStopsModifiersFromRequest(Request $request)
    {
        if ($request->query->has('stop')) {
            $stop = $request->query->get('stop');
            $stop = Stop::reference($stop);

            yield new RelatedFilter($stop);
        }

        if ($request->query->has('track') || $request->attributes->has('track')) {
            $track = $request->get('track');
            $track = Track::reference($track);

            yield new RelatedFilter($track);
        }

        if ($request->query->has('id')) {
            $id = encapsulate($request->query->get('id'));

            yield new IdFilter($id);
        }
    }
}
