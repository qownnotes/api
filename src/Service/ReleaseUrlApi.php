<?php

declare(strict_types=1);

namespace App\Service;

class ReleaseUrlApi
{
    /**
     * https://docs.github.com/en/rest/reference/repos#get-the-latest-release.
     */
    public function getReleasesRequestUrl(string $owner, string $repo): string
    {
        return sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode($owner),
            rawurlencode($repo),
        );
    }

    public function getChangeLogUrl(string $tag): string
    {
        return sprintf(
            'https://raw.githubusercontent.com/pbek/QOwnNotes/%s/CHANGELOG.md',
            rawurlencode($tag),
        );
    }
}
