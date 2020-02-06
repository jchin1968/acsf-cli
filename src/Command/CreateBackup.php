<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class CreateBackup extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('backups:create')
      ->setDescription('Create site(s) backup')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
      )
      ->addArgument(
        'site_ids',
        InputArgument::IS_ARRAY | InputArgument::REQUIRED,
        'A list of Site IDs (space delimited) to backup. Use "all" to backup all primary sites.'
      )
      ->addOption(
        'label',
        'l',
        InputOption::VALUE_OPTIONAL,
        'The human-readable description of this backup.'
      )
      ->addOption(
        'callback_url',
        'u',
        InputOption::VALUE_OPTIONAL,
        'The callback URL, which is invoked upon completion.'
      )
      ->addOption(
        'callback_method',
        'm',
        InputOption::VALUE_OPTIONAL,
        'The callback method, "GET", or "POST". Uses "POST" if empty.'
      )
      ->addOption(
        'caller_data',
        'r',
        InputOption::VALUE_OPTIONAL,
        'Data that should be included in the callback, json encoded.'
      )
      ->addOption(
        'components',
        'c',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Components to backup. Possible values are: codebase, database, public files, private files and themes. Omitting this option will backup every component. To specify multiple components, prefix each one with -c.'
      )
      ->addOption(
        'include_non_primary',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Include non-primary sites in the backup. Omitting this flag will backup primary sites only',
        false
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $site_ids = $input->getArgument('site_ids');

    $data = array_filter([
      'label' => $input->getOption('label'),
      'callback_url' => $input->getOption('callback_url'),
      'callback_method' => $input->getOption('callback_method'),
      'caller_data' => $input->getOption('caller_data'),
      'components' => array_map(function ($component) {
        return $component; },
        $input->getOption('components')),
     ]);

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    // If first site_id argument is 'all' then fetch all sites to backup
    if ($site_ids[0] == 'all') {
      // Get a list of all sites.
      $response = $client->request('GET', "sites", [
        'query' => [
          'page' => 1,
          'limit' => 5000
        ]
      ]);
      $response_body = $response->getBody();
      $response_body = json_decode($response_body, TRUE);
      $sites = $response_body['sites'];

      // Rebuild $site_ids array.
      $site_ids = [];
      foreach ($sites as $site) {
        // Only include primary sites unless include_non_primary flag is set.
        $include_non_primary = $input->getOption('include_non_primary') !== false;
        if ($site['is_primary'] || $include_non_primary) {
            $site_ids[] = $site['id'];
        }
      }

      // Exit if no sites are found.
      if (empty($site_ids)) {
        $io->warning('No sites found');
        return;
      }
    }

    // Create backup for each site.
    foreach ($site_ids as $index => $site_id) {
      $response = $client->request('POST', "sites/$site_id/backup", [
        'headers' => [
          'Content-Type' => 'application/json'
        ],
      ]);
      $results = $response->getBody();
      $results = json_decode($results, TRUE);
      $io->success("Task created: {$results['task_id']}");
    }
  }
}
