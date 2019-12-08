<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Stop;
use App\Model\StopGroup;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Service\Proxy\ReferenceFactory;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StopsController
 *
 * @package App\Controller
 * @Route("/stops")
 *
 * @SWG\Tag(name="stops")
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
        switch (true) {
            case $request->query->has('id'):
                $result = $stops->getManyById($request->query->get('id'));
                break;

            case $request->query->has('name'):
                $result = $stops->findGroupsByName($request->query->get('name'));
                break;

            default:
                $result = $stops->getAllGroups();
        }

        return $this->json($result->all());
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
        switch (true) {
            case $request->query->has('id'):
                $result = $stops->getManyById($request->query->get('id'));
                break;

            case $request->query->has('name'):
                $result = $stops->findGroupsByName($request->query->get('name'));
                break;

            default:
                $result = $stops->getAllGroups();
        }

        return $this->json($result->all());
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
        return $this->json($stops->getById($id));
    }

    /**
     * @Route("/{id}/tracks", methods={"GET"})
     */
    public function tracks(ReferenceFactory $reference, TrackRepository $tracks, $id)
    {
        $stop = $reference->get(Stop::class, $id);

        return $this->json($tracks->getByStop($stop)->map(function ($tuple) {
            return array_combine(['track', 'order'], $tuple);
        }));
    }
}