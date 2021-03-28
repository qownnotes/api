<?php

declare(strict_types=1);

namespace App\Service;

use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class ReleaseUrlApi
{
    /**
     * https://docs.github.com/en/rest/reference/repos#get-the-latest-release
     *
     * @param string $owner
     * @param string $repo
     * @return string
     * @throws UriException
     */
    public function getReleasesRequestUrl(string $owner, string $repo): string
    {
        $uriTemplate = new UriTemplate('https://api.github.com/repos/{owner}/{repo}/releases/latest');

        return (string) $uriTemplate->expand([
                'owner' => $owner,
                'repo' => $repo,
            ]);
    }

    /**
     * @param string $tag
     * @return string
     * @throws UriException
     */
    public function getChangeLogUrl(string $tag): string
    {
        $uriTemplate = new UriTemplate('https://raw.githubusercontent.com/pbek/QOwnNotes/{tag}/CHANGELOG.md');

        return (string) $uriTemplate->expand([
                'tag' => $tag,
            ]);
    }
}
