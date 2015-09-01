<?php
namespace Scheb\Tombstone\Analyzer\Cli;

use PhpParser\Lexer;
use Scheb\Tombstone\Analyzer\Analyzer;
use Scheb\Tombstone\Analyzer\AnalyzerResult;
use Scheb\Tombstone\Analyzer\Log\LogDirectoryScanner;
use Scheb\Tombstone\Analyzer\Log\LogReader;
use Scheb\Tombstone\Analyzer\Matching\MethodNameStrategy;
use Scheb\Tombstone\Analyzer\Matching\PositionStrategy;
use Scheb\Tombstone\Analyzer\Report\ConsoleReportGenerator;
use Scheb\Tombstone\Analyzer\Report\HtmlReportGenerator;
use Scheb\Tombstone\Analyzer\Source\SourceDirectoryScanner;
use Scheb\Tombstone\Analyzer\Source\TombstoneExtractorFactory;
use Scheb\Tombstone\Analyzer\TombstoneIndex;
use Scheb\Tombstone\Analyzer\VampireIndex;
use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends AbstractCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('tombstone')
            ->addArgument('source-dir', InputArgument::REQUIRED, 'Path to PHP source files')
            ->addArgument('log-dir', InputArgument::REQUIRED, 'Path to the log files')
            ->addOption('report-html', 'rh', InputOption::VALUE_REQUIRED, 'Generate HTML report to a directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $htmlReportDir = $input->getOption('report-html');
        $sourceDir = realpath($input->getArgument('source-dir'));
        if (!$sourceDir) {
            $output->writeln('Argument "source-dir" is not a valid directory');
            return 1;
        }

        $logDir = realpath($input->getArgument('log-dir'));
        if (!$logDir) {
            $output->writeln('Argument "log-dir" is not a valid directory');
            return 1;
        }

        $result = $this->createResult($sourceDir, $logDir);
        $report = new ConsoleReportGenerator($output);
        $report->generate($result);

        if ($htmlReportDir) {
            $this->generateHtmlReport($htmlReportDir, $result);
        }

        return 0;
    }

    /**
     * @param string $sourceDir
     * @param string $logDir
     *
     * @return AnalyzerResult
     */
    private function createResult($sourceDir, $logDir) {
        $sourceScanner = new SourceDirectoryScanner(TombstoneExtractorFactory::create(new TombstoneIndex($sourceDir)), $sourceDir);
        $tombstoneIndex = $sourceScanner->getTombstones();

        $logScanner = new LogDirectoryScanner(new LogReader(new VampireIndex()), $logDir);
        $vampireIndex = $logScanner->getVampires();

        $analyzer = new Analyzer([
            new MethodNameStrategy(),
            new PositionStrategy(),
        ]);

        return $analyzer->getResult($tombstoneIndex, $vampireIndex);
    }

    /**
     * @param string $reportDir
     * @param AnalyzerResult $result
     */
    protected function generateHtmlReport($reportDir, AnalyzerResult $result)
    {
        $this->output->writeln('');
        $this->output->write('Generate HTML report...');
        try {
            $report = new HtmlReportGenerator($reportDir);
            $report->generate($result);
            $this->output->writeln(' Done');
        } catch (\Exception $e) {
            $this->output->writeln('Could not generate HTML report: '.$e->getMessage());
        }
    }
}
