<?php

namespace SiteFactoryAPI\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;

abstract class AcsfCommandBase extends Command {

  /**
   * Retrieve a list of all sites from Site Factory.
   *
   * Use this method instead of the default GET sites API call when you need
   * to retrieve more than 100 records.
   */
  protected function getAllSites(Client $client) {
    $page = 1;
    $more_pages = TRUE;
    $sites = [];

    while ($more_pages) {
      // Assume this will be the last run.
      $more_pages = FALSE;

      // Fetch list of sites.
      $response = $client->request('GET', "sites", [
        'query' => [
          'page' => $page,
          'limit' => 100
        ]
      ]);

      // Extract sites data and merge into $sites array.
      $response_body = $response->getBody();
      $response_body = json_decode($response_body, TRUE);
      $sites = array_merge($sites, $response_body['sites']);

      // Check header for next page link to indicate if there are more sites to retrieve.
      $links = $response->getHeader('link');
      if (!empty($links)) {
        foreach($links as $link) {
          if (strpos($link, 'rel="next"') !== FALSE) {
            // Next page link was found.
            $page++;
            $more_pages = TRUE;
          }
        }
      }
    }
    return $sites;
  }
}