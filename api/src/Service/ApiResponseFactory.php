<?php

namespace App\Service;

use App\Dto\CollectionResult;
use App\Dto\Links\CollectionLinks;
use App\Dto\Links\Link;
use App\Filter\Requirement\Embed;
use App\Utility\IterableUtils;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ContentTypeResolver $contentTypeResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function createResponse(
        mixed $data,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $context = [],
        Request $request = null,
    ): Response {
        if ($contentType = $this->contentTypeResolver->getContentType($data)) {
            $headers['Content-Type'] = sprintf(
                "application/%s+json",
                $contentType
            );
        }

        return new JsonResponse(
            data: $this->serializer->serialize($data, 'json', $context),
            status: $status,
            headers: $headers,
            json: true,
        );
    }

    public function createCollectionResponse(
        iterable $items,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $context = [],
        Request $request = null,
    ): Response {
        $request ??= $this->requestStack->getCurrentRequest();

        $accept = AcceptHeader::fromString($request->headers->get('Accept'));

        if ($accept->has("application/vnd.cojedzie.collection+json")) {
            $list = CollectionResult::createFromIterable(
                items: $items,
                total: count($items),
                links: new CollectionLinks(
                    self: new Link($request->getUri()),
                )
            );
        } else {
            $list = array_values(IterableUtils::toArray($items));
        }

        return $this->createResponse($list, $status, $headers, $context, $request);
    }

    public function extractGroupsFromRequirements(iterable $requirements): array
    {
        $groups = [];

        foreach ($requirements as $requirement) {
            if ($requirement instanceof Embed) {
                $groups[] = sprintf('embed:%s', $requirement->getRelationship());
            }
        }

        return $groups;
    }
}
