<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class CreateBackupBulk extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('backups:create:bulk')
      ->setDescription('Create backups for multiple sites')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
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
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    // Get a list of primary sites.
    $response = $client->request('GET', "sites", [
      'query' => [
        'page' => 1,
        // Must set a large limit since the API does not support filtering.
        'limit' => 1000
      ]
    ]);
    $data = $response->getBody();
    $data = json_decode($data, TRUE);
    $sites = $data['sites'];

    // Apply filters.
    foreach ($sites as $index => $site) {
      // Remove non-primary sites. 
      if (!$site['is_primary']) {
        unset($sites[$index]);
      }
    }

    // Exit if no sites.
    if (empty($sites)) {
      return;
    }

    // Define backup parameters.
    $data = array_filter([
      'label' => $input->getOption('label'),
      'callback_url' => $input->getOption('callback_url'),
      'callback_method' => $input->getOption('callback_method'),
      'caller_data' => $input->getOption('caller_data'),
      'components' => array_map(function ($component) {
        return $component; },
        $input->getOption('components')),
     ]);

    // Create backup for each site.
    foreach ($sites as $index => $site) {
      var_export($site);
      
      $site_id = $site['id'];    
      $response = $client->request('POST', "sites/$site_id/backup", [
        'headers' => [
          'Content-Type' => 'application/json'
        ]
      ]);
      $results = $response->getBody();
      $results = json_decode($results, TRUE);

      $io->success("Task created: {$results['task_id']}");
    }
  }
}
