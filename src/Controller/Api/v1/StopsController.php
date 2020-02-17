<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Stop;
use App\Model\Track;
use App\Model\StopGroup;
use App\Modifier\IdFilter;
use App\Modifier\FieldFilter;
use App\Modifier\IncludeDestinations;
use App\Modifier\RelatedFilter;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Service\Proxy\ReferenceFactory;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Tightenco\Collect\Support\Collection;

/**
 * Class StopsController
 *
 * @package App\Controller
 * @Route("/stops")
 *
 * @SWG\Tag(name="Stops")
 * @SWG\Parameter(ref="#/parameters/provider")
 */
class StopsController extends Controller
{
    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns all stops for specific provider, e.g. ZTM Gdańsk.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Stop::class)))
     * )
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     type="array",
     *     description="Stop identificators to retrieve at once. Can be used to bulk load data. If not specified will return all data.",
     *     @SWG\Items(type="string")
     * )
     *
     * @Route("/", methods={"GET"})
     */
    public function index(Request $request, StopRepository $stops)
    {
        $modifiers = $this->getModifiersFromRequest($request);

        return $this->json($stops->all(...$modifiers)->toArray());
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns grouped stops for specific provider, e.g. ZTM Gdańsk.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=StopGroup::class)))
     * )
     *
     * @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="Part of the stop name to search for.",
     * )
     *
     * @Route("/groups", methods={"GET"})
     */
    public function groups(Request $request, StopRepository $stops)
    {
        $modifiers = $this->getModifiersFromRequest($request);

        return $this->json(static::group($stops->all(...$modifiers))->toArray());
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @SWG\Schema(ref=@Model(type=Stop::class))
     * )
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Stop identificator as provided by data provider."
     * )
     *
     * @Route("/{id}", methods={"GET"})
     */
    public function one(Request $request, StopRepository $stops, $id)
    {
        return $this->json($stops->first(new IdFilter($id), new IncludeDestinations()));
    }

    /**
     * @Route("/{id}/tracks", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @SWG\Schema(type="object", properties={
     *         @SWG\Property(property="track", type="object", ref=@Model(type=Track::class)),
     *         @SWG\Property(property="order", type="integer", minimum="0")
     *     })
     * )
     *
     * @SWG\Tag(name="Tracks")
     */
    public function tracks(ReferenceFactory $reference, TrackRepository $tracks, $id)
    {
        $stop = $reference->get(Stop::class, $id);

        return $this->json($tracks->getByStop($stop)->map(function ($tuple) {
            return array_combine(['track', 'order'], $tuple);
        }));
    }

    public static function group(Collection $stops)
    {
        return $stops->groupBy(function (Stop $stop) {
            return $stop->getGroup();
        })->map(function ($stops, $key) {
            $group = new StopGroup();

            $group->setName($key);
            $group->setStops($stops);

            return $group;
        })->values();
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getModifiersFromRequest(Request $request): array
    {
        $modifiers = [];

        if ($request->query->has('name')) {
            $modifiers[] = FieldFilter::contains('name', $request->query->get('name'));
        }

        if ($request->query->has('id')) {
            $modifiers[] = new IdFilter($request->query->get('id'));
        }

        if ($request->query->has('include-destinations')) {
            $modifiers[] = new IncludeDestinations();
        }

        return $modifiers;
    }
}
