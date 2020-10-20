<?php

namespace App\Controller;

use App\Entity\AppRelease;
use DOMDocument;
use Michelf\Markdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AppReleaseRSSFeedController extends AbstractController
{
    /**
     * Returns the RSS feed for the app releases
     *
     * http://api.qownnotes.org/rss/app-releases
     *
     * @Route("/rss/app-releases")
     * @return Response
     */
    public function appReleases()
    {
        $projectUrl = "https://www.qownnotes.org";

        $xml = new DOMDocument("1.0", "UTF-8"); // Create new DOM document.

        // create "RSS" element
        $rss = $xml->createElement("rss");
        $rssNode = $xml->appendChild($rss); // Add RSS element to XML node
        $rssNode->setAttribute("version", "2.0"); // Set RSS version

        // set attributes
        $rssNode->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/"); // xmlns:dc (info http://j.mp/1mHIl8e )
        $rssNode->setAttribute(
            "xmlns:content",
            "http://purl.org/rss/1.0/modules/content/"
        ); // xmlns:content (info http://j.mp/1og3n2W)
        $rssNode->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");// xmlns:atom (http://j.mp/1tErCYX )

        // Create RFC822 Date format to comply with RFC822
        $dateF = date("D, d M Y H:i:s T", time());
        $buildDate = gmdate(DATE_RFC2822, strtotime($dateF));

        // create "channel" element under "RSS" element
        $channel = $xml->createElement("channel");
        $channelNode = $rssNode->appendChild($channel);

        // a feed should contain an atom:link element (info http://j.mp/1nuzqeC)
        $channelAtomLink = $xml->createElement("atom:link");
        $channelAtomLink->setAttribute("href", "https://api.qownnotes.org/rss/app-releases"); // url of the feed
        $channelAtomLink->setAttribute("rel", "self");
        $channelAtomLink->setAttribute("type", "application/rss+xml");
        $channelNode->appendChild($channelAtomLink);

        // add general elements under "channel" node
        $channelNode->appendChild($xml->createElement("title", "QOwnNotes release versions")); // title
        $channelNode->appendChild($xml->createElement("description", "New releases of QOwnNotes"));  // description
        $channelNode->appendChild($xml->createElement("link", $projectUrl)); // website link
        $channelNode->appendChild($xml->createElement("language", "en-gb"));  // language
        $channelNode->appendChild($xml->createElement("lastBuildDate", $buildDate));  // last build date
        $channelNode->appendChild($xml->createElement("generator", "PHP DOMDocument")); // generator

        $repository = $this->getDoctrine()
            ->getRepository('App:AppRelease');

        $criteria = [];
        $orderBy = ["dateCreated" => "DESC"];

        /** @var AppRelease[] $appReleases */
        $appReleases = $repository->findBy($criteria, $orderBy, 100);

        if (count($appReleases) > 0) {
            foreach ($appReleases as $appRelease) {
                $version = $appRelease->getVersion();
                $title = "New release of QOwnNotes: v" . $version;
                $description = $appRelease->getReleaseChangesMarkdown();
                $url = "$projectUrl/changelog.html#_" . str_replace(".", "-", $version);
                $changeLogHTML = Markdown::defaultTransform($description);

                $changeLogHTML = strip_tags(
                    $changeLogHTML,
                    '<br><br/><h1><h2><h3><ul><ol><li><p><pre><code><a><em><strong>'
                );

                $itemNode = $channelNode->appendChild($xml->createElement("item")); // create a new node called "item"
                $itemNode->appendChild($xml->createElement("title", $title)); // Add Title under "item"
                $itemNode->appendChild($xml->createElement("link", $url)); // add link node under "item"

                // Unique identifier for the item (GUID)
                $guidLink = $xml->createElement("guid", "qon-app-release-" . $appRelease->getId());
                $guidLink->setAttribute("isPermaLink", "false");
                $itemNode->appendChild($guidLink);

                // create "description" node under "item"
                $description_node = $itemNode->appendChild($xml->createElement("description"));

                // fill description node with CDATA content
                $description_contents = $xml->createCDATASection($changeLogHTML);
                $description_node->appendChild($description_contents);

                // Published date
                $dateRfc = $appRelease->getDateCreated()->format(\DateTime::RFC2822);
                $pubDate = $xml->createElement("pubDate", $dateRfc);

                $itemNode->appendChild($pubDate);
            }
        }

        $xmlString = $xml->saveXML();

        return new Response($xmlString, 200, ['Content-Type' => 'text/xml']);
    }
}
