<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Stop;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Service\Proxy\ReferenceFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StopsController
 *
 * @package App\Controller
 * @Route("/stops")
 */
class StopsController extends Controller
{
    /**
     * @Route("/", methods={"GET"})
     */
    public function index(Request $request, StopRepository $stops)
    {
        $result = $request->query->has('id')
            ? $stops->getManyById($request->query->get('id'))
            : $stops->getAllGroups();

        return $this->json($result->all());
    }

    /**
     * @Route("/search", methods={"GET"})
     */
    public function find(Request $request, StopRepository $stops)
    {
        $result = $request->query->has('name')
            ? $stops->findGroupsByName($request->query->get('name'))
            : $stops->getAllGroups();

        return $this->json($result->all());
    }

    /**
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