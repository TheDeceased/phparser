<?php

namespace TheDeceased\PHParser\UI\CLI\Commands;

use Symfony\Component\Filesystem\Filesystem;
use TheDeceased\PHParser\Parser;

class ParseCommand
{
    const OPTIONS = "dcoh";
    const ARGUMENTS = [];

    private $arguments;
    private $options;
    private $input;
    private $out;
    private $fs;

    public function __construct($arguments, $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;
        $this->fs = new Filesystem();
    }

    /**
     * @return null
     */
    public function run()
    {
        if (empty($this->arguments) || empty($this->arguments[0]) || $this->isHelp()) {
            return $this->drawHelp();
        }

        $this->input = $this->arguments[0];
        $this->out = !empty($this->arguments[1]) ? $this->arguments[1] : '';

        if (!$this->fs->exists($this->input)) {
            throw new \RuntimeException("{$this->input} does not exists or cant be read" . PHP_EOL);
        }
        if (is_dir($this->input) && !$this->isDirectoryParsed()) {
            echo "{$this->input} is a directory. To parse a directory use -d option" . PHP_EOL;
            return null;
        }
        if ($this->mustWriteToFile() && $this->cleanupNeeded()) {
            $this->fs->remove($this->out);
        }
        if (is_dir($this->input)) {
            $this->parseDirectory($this->input);
        } else {
            $this->parseFile($this->input);
        }
        return null;
    }

    private function parseFile($input, $inputDir = '')
    {
        $parser = new Parser();
        switch (true) {
            case $this->mustWriteToFile():
                $outPath = $this->outPath($input, $inputDir);
                if (!$this->isOverride() && $this->fs->exists($outPath)) {
                    throw new \RuntimeException(
                        "File {$outPath} already exists. If you want to override current content use -o key" . PHP_EOL
                    );
                }
                $directory = pathinfo($outPath, PATHINFO_DIRNAME);
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($outPath, $parser->parse(file_get_contents($input)));
                break;
            default:
                echo $parser->parse(file_get_contents($input));
        }
    }

    private function parseDirectory($input)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($input, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                $this->parseFile($item->getPathname(), $input);
            }
        }
    }

    private function outPath($input, $inputDir)
    {
        if (!$this->isDirectoryParsed()) {
            return $this->out;
        }
        return $this->combinePath($this->out, rtrim($this->fs->makePathRelative($input, $inputDir), '/'));
    }

    /**
     * @param string $start
     * @param string $end
     *
     * @return string
     */
    private function combinePath($start, $end)
    {
        return rtrim($start, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($end, DIRECTORY_SEPARATOR);
    }

    /**
     * @return bool
     */
    private function isDirectoryParsed()
    {
        return isset($this->options['d']);
    }

    /**
     * @return bool
     */
    private function isOverride()
    {
        return isset($this->options['o']);
    }

    private function cleanupNeeded()
    {
        return isset($this->options['c']);
    }

    private function isHelp()
    {
        return isset($this->options['h']);
    }

    private function mustWriteToFile()
    {
        return !empty($this->out);
    }

    private function drawHelp()
    {
        echo "USAGE: phparser [-ro] INPUT [OUTPUT]". PHP_EOL;
        echo PHP_EOL;
        echo "INPUT - path to file or directory to parse" . PHP_EOL;
        echo "OUTPUT - path to file or directory to write the results" . PHP_EOL;
        echo "-d - to recursively parse directory, not a file". PHP_EOL;
        echo "-o - to override file content if it already exists". PHP_EOL;
        echo "-c - to remove target directory before start". PHP_EOL;
        return null;
    }
}
