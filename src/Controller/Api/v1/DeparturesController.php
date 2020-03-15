<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Departure;
use App\Modifier\FieldFilter;
use App\Modifier\IdFilter;
use App\Modifier\Limit;
use App\Modifier\With;
use App\Provider\DepartureRepository;
use App\Provider\StopRepository;
use App\Service\SerializerContextFactory;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function Kadet\Functional\ref;
use function Kadet\Functional\Transforms\property;

/**
 * Class DeparturesController
 *
 * @Route("/departures")
 * @SWG\Tag(name="Departures")
 * @SWG\Parameter(ref="#/parameters/provider")
 */
class DeparturesController extends Controller
{
    /**
     * @Route("/{stop}", methods={"GET"})
     * @SWG\Response(
     *     description="Gets departures from particular stop.",
     *     response=200,
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departure::class)))
     * )
     */
    public function stop(DepartureRepository $departures, StopRepository $stops, $stop, Request $request)
    {
        $stop = $stops->first(new IdFilter($stop));

        return $this->json($departures->current(collect($stop), ...$this->getModifiersFromRequest($request)));
    }

    /**
     * @Route("/", methods={"GET"})
     * @SWG\Response(
     *     description="Gets departures from given stops.",
     *     response=200,
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departure::class)))
     * )
     *
     * @SWG\Parameter(
     *     name="stop",
     *     description="Stop identifiers.",
     *     type="array",
     *     in="query",
     *     @SWG\Items(type="string")
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     description="Max departures count.",
     *     type="integer",
     *     in="query"
     * )
     */
    public function stops(DepartureRepository $departures, StopRepository $stops, Request $request)
    {
        $stops  = $stops->all(new IdFilter($request->query->get('stop')));
        $result = $departures->current($stops, ...$this->getModifiersFromRequest($request));

        return $this->json(
            $result->values()->slice(0, (int)$request->query->get('limit', 8)),
            200,
            [],
            $this->serializerContextFactory->create(Departure::class, ['Default'])
        );
    }

    private function getModifiersFromRequest(Request $request)
    {
        if ($request->query->has('limit')) {
            yield Limit::count($request->query->getInt('limit'));
        }
    }
}
