<?php declare(strict_types = 1);

namespace mihaeu\phpDependencies;

class FileFinder
{
    public function find(\SplFileInfo $file) : PhpFileCollection
    {
        return $file->isDir()
            ? $this->findInDir($file)
            : new PhpFileCollection();
    }

    private function findInDir(\SplFileInfo $dir) : PhpFileCollection
    {
        $collection = new PhpFileCollection();
        $regexIterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir->getPathname())
            ), '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH
        );
        foreach ($regexIterator as $fileName) {
            $collection->add(new PhpFile(new \SplFileInfo($fileName[0])));
        }
        return $collection;
    }
}