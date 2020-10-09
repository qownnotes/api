<?php

declare(strict_types=1);

namespace App\Service;

use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class ReleaseUrlApi
{
    /**
     * https://docs.github.com/en/free-pro-team@latest/rest/reference/repos#releases
     *
     * @param string $owner
     * @param string $repo
     * @return string
     * @throws UriException
     */
    public function getReleasesRequestUrl(string $owner, string $repo): string
    {
        $uriTemplate = new UriTemplate('https://api.github.com/repos/{owner}/{repo}/releases');

        return (string) $uriTemplate->expand([
                'owner' => $owner,
                'repo' => $repo,
            ]);
    }
}
