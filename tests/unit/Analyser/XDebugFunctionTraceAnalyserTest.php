<?php

declare(strict_types=1);

namespace Mihaeu\PhpDependencies\Analyser;

use InvalidArgumentException;
use Mihaeu\PhpDependencies\Dependencies\DependencyFactory;
use Mihaeu\PhpDependencies\DependencyHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @covers Mihaeu\PhpDependencies\Analyser\XDebugFunctionTraceAnalyser
 */
class XDebugFunctionTraceAnalyserTest extends TestCase
{
    /** @var XDebugFunctionTraceAnalyser */
    private $xDebugFunctionTraceAnalyser;

    /** @var SplFileInfo */
    private $tempFile;

    protected function setUp(): void
    {
        $this->xDebugFunctionTraceAnalyser = new XDebugFunctionTraceAnalyser($this->createMock(DependencyFactory::class));
        $this->tempFile = new \SplFileInfo(sys_get_temp_dir().'/'.'dephpend-trace.sample');
        touch($this->tempFile->getPathname());
    }

    protected function tearDown(): void
    {
        unlink($this->tempFile->getPathname());
    }

    public function testAnalyse(): void
    {
        $this->writeContent([
            [0, 1, 2, 3, 4, 'B->c', 6, 7, 8, 9, 10, 'class A'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'class A'],
        ]);
        $this->assertEquals(
            DependencyHelper::map('
                B --> A
                D --> A
            '),
            $this->xDebugFunctionTraceAnalyser->analyse($this->tempFile)
        );
    }

    public function testAnalyseIgnoresScalarValues(): void
    {
        $this->writeContent([
            [0, 1, 2, 3, 4, 'B->c', 6, 7, 8, 9, 10, '???', 'class A'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'string(10)'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'long'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'true'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'false'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'null'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'int'],
            [0, 1, 2, 3, 4, 'D->c', 6, 7, 8, 9, 10, 'resource'],
        ]);
        $this->assertEquals(
            DependencyHelper::map('
                B --> A
            '),
            $this->xDebugFunctionTraceAnalyser->analyse($this->tempFile)
        );
    }

    public function testThrowsExceptionIfFileCannotBeOpened(): void
    {
        $tmpFile = $this->createMock(SplFileInfo::class);
        $tmpFile->expects($this->once())->method('getPathname')->willReturn('');
        $this->expectException(InvalidArgumentException::class);
        $this->xDebugFunctionTraceAnalyser->analyse($tmpFile);
    }

    private function createContent(array $data): string
    {
        return array_reduce($data, function (string $carry, array $lineParts) {
            return $carry.implode("\t", $lineParts).PHP_EOL;
        }, '');
    }

    private function writeContent(array $data): void
    {
        file_put_contents($this->tempFile->getPathname(), $this->createContent($data));
    }
}
