<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class StatusTask extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('status:task')
      ->setDescription('Display current status for a task.')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
      )
      ->addArgument(
        'task_id',
        InputArgument::REQUIRED,
        'Task ID'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $task_id = $input->getArgument('task_id');

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    $response = $client->request('GET', "wip/task/$task_id/status");
    $data = $response->getBody();
    $data = json_decode($data, TRUE);

    $result = $data['wip_task'];

    if (empty($result)) {
      return;
    }

    // Display results as a table.
    $header = ['Property', 'Value'];
    $properties = [];
    foreach ($result as $property => $value) {
      // Convert timestamp to ISO 8601 format.
      if (in_array($property, ['added', 'started', 'completed'])) {
        $value .= "  (" . date('c', $value) . ")";
      }
      $properties[] = [$property, $value];
    }
    $io->table($header, $properties);
  }
}

