<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Line;
use App\Model\Stop;
use App\Model\Track;
use App\Modifier\IdFilter;
use App\Modifier\RelatedFilter;
use App\Provider\TrackRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function App\Functions\encapsulate;

/**
 * @Route("/tracks")
 */
class TracksController extends Controller
{
    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns all tracks for specific provider, e.g. ZTM Gdańsk.",
     * )
     * @SWG\Tag(name="Tracks")
     * @Route("/", methods={"GET"})
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
                throw new BadRequestHttpException(
                    sprintf(
                        'At least one parameter of %s must be set.',
                        implode(', ', ['stop', 'line', 'id'])
                    )
                );
        }
    }

    private function byId(Request $request, TrackRepository $repository)
    {
        $id = encapsulate($request->query->get('id'));

        return $this->json($repository->all(new IdFilter($id)));
    }

    private function byStop(Request $request, TrackRepository $repository)
    {
        $stop = $request->query->get('stop');
        $stop = array_map([Stop::class, 'reference'], encapsulate($stop));

        return $this->json($repository->getByStop($stop));
    }

    private function byLine(Request $request, TrackRepository $repository)
    {
        $line = $request->query->get('line');
        $line = Line::reference($line);

        return $this->json($repository->all(new RelatedFilter($line)));
    }
}
