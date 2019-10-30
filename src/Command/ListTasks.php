<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class ListTasks extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('task:list')
      ->setDescription('List tasks.')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
      )
      ->addOption(
        'limit',
        'l',
        InputOption::VALUE_OPTIONAL,
        'A positive integer (max 100).',
        10
      )
      ->addOption(
        'page',
        'p',
        InputOption::VALUE_OPTIONAL,
        'A positive integer.',
        1
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    $response = $client->request('GET', "tasks", [
      'query' => [
        'page' => $input->getOption('page'),
        'limit' => $input->getOption('limit')
      ]
    ]);
    $data = $response->getBody();
    $data = json_decode($data, TRUE);

    if (empty($data)) {
      return;
    }

    // Hide some of the fields.
    foreach ($data as $key => &$task) {
      unset($data[$key]['lease']);
      unset($data[$key]['object_id']);
      unset($data[$key]['lease']);
      unset($data[$key]['max_run_time']);
      unset($data[$key]['concurrency_exceeded']);
      unset($data[$key]['nid']);
      unset($data[$key]['uid']);
      unset($data[$key]['class']);
    }

    $io->table(array_keys($data[0]), $data);
  }
}

 ?>
