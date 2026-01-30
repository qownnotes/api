<?php

namespace App\Controller;

use App\Service\ReleaseApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class LegacyApiController extends AbstractController
{
    private $api;

    private $requestStack;

    public function __construct(ReleaseApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws \Exception
     */
    #[Route('/api/v1/last_release/QOwnNotes/{id}.json')]
    public function lastRelease($id): JsonResponse
    {
        $version = $this->requestStack->getCurrentRequest()->get('v');
        $updateMode = $this->requestStack->getCurrentRequest()->get('um');
        $debug = $this->requestStack->getCurrentRequest()->get('debug');
        $release = $this->requestStack->getCurrentRequest()->get('r');
        $os = $this->requestStack->getCurrentRequest()->get('o');
        $cid = $this->requestStack->getCurrentRequest()->get('cid');

        $filter = [
            'version' => $version,
            'um' => $updateMode,
            'debug' => $debug,
            'release' => $release,
            'os' => $os,
            'cid' => $cid,
        ];

        $latestRelease = $this->api->fetchLatestRelease($id, $filter);

        $result = [
            'should_update' => $latestRelease->getNeedUpdate(),
            'release_version_string' => $latestRelease->getVersion(),
            'changes_html' => $latestRelease->getReleaseChangesHtml(),
            'release' => [
                'assets' => [0 => ['browser_download_url' => $latestRelease->getUrl()]],
            ],
        ];

        return $this->json($result);
    }
}
