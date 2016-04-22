<?php

namespace Pimgento\Import\Console\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Pimgento\Import\Model\Import as ImportModel;
use \Exception;

class PimgentoImportCommand extends Command
{

    const IMPORT_CODE = 'code';

    const IMPORT_FILE = 'file';

    /**
     * @var \Pimgento\Import\Model\Import
     */
    protected $_import;

    /**
     * PimgentoImportCommand constructor.
     *
     * @param \Pimgento\Import\Model\Import $import
     * @param null $name
     */
    public function __construct(ImportModel $import, $name = null)
    {
        parent::__construct($name);
        $this->_import = $import;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pimgento:import')
            ->setDescription('Import PIM files to Magento')
            ->addOption(self::IMPORT_CODE, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::IMPORT_FILE, null, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getOption(self::IMPORT_CODE);
        $file = $input->getOption(self::IMPORT_FILE);

        if (!$code) {
            $this->_usage($output);
        } else {
            $this->_import($code, $file, $output);
        }
    }

    /**
     * Run import
     *
     * @param string $code
     * @param string $file
     * @param OutputInterface $output
     */
    protected function _import($code, $file, OutputInterface $output)
    {
        try {
            $import = $this->_import->load($code);
            $import->setFile($file)->setStep(0);

            while ($import->canExecute()) {
                $import->execute();

                $output->writeln($import->getComment());
                $output->writeln($import->getMessage());

                if (!$import->getContinue()) {
                    break;
                }

                $import->next();
            }
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    /**
     * Print command usage
     *
     * @param OutputInterface $output
     */
    protected function _usage(OutputInterface $output)
    {
        $imports = $this->_import->getCollection();

        /* Options */
        $output->writeln('<comment>' . __('Options:') . '</comment>');
        $output->writeln(' <info>--code</info>');
        $output->writeln(' <info>--file</info>');
        $output->writeln('');

        /* Codes */
        $output->writeln('<comment>' . __('Available codes:') . '</comment>');
        foreach ($imports as $import) {
            $output->writeln(' <info>' . $import->getCode() . '</info>');
        }
        $output->writeln('');

        /* Example */
        $import = $imports->getFirstItem();
        if ($import->getCode()) {
            $output->writeln('<comment>' . __('Example:') . '</comment>');
            $output->writeln(
                ' <info>pimgento:import --code=' . $import->getCode() . ' --file=' . $import->getCode() . '.csv</info>'
            );
        }
    }

}
