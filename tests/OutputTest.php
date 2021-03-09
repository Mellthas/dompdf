<?php
namespace Dompdf\Tests;

use CallbackFilterIterator;
use Dompdf\Tests\TestCase;
use Dompdf\Tests\OutputTest\Dataset;
use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class OutputTest extends TestCase
{
    /**
     * @return Iterator<Dataset>
     */
    public static function datasets(): Iterator
    {
        $flags = FilesystemIterator::KEY_AS_FILENAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;
        $filter = function (SplFileInfo $file) {
            return $file->getExtension() === "html";
        };
        $dir = new RecursiveDirectoryIterator(Dataset::DATASET_DIRECTORY, $flags);
        $files = new CallbackFilterIterator(new RecursiveIteratorIterator($dir), $filter);

        foreach ($files as $file) {
            $dataset = new Dataset($file);
            yield $dataset->name() => $dataset;
        }
    }

    public function outputTestProvider(): Iterator
    {
        foreach (self::datasets() as $name => $dataset) {
            yield $name => [$dataset];
        }
    }

    protected function compareImages(string $referencePath, string $imageData): bool
    {
        $image1 = imagecreatefrompng($referencePath);
        $image2 = imagecreatefromstring($imageData);
        $width = imagesx($image1);
        $height = imagesy($image1);
        $width2 = imagesx($image2);
        $height2 = imagesy($image2);

        if ($width !== $width2 || $height !== $height2) {
            return false;
        }

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color1 = imagecolorat($image1, $x, $y);
                $color2 = imagecolorat($image2, $x, $y);

                if ($color1 !== $color2) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @dataProvider outputTestProvider
     */
    public function testOutput(Dataset $dataset): void
    {
        $pdf = $dataset->render("gd");
        $pages = range(1, $pdf->getCanvas()->get_page_count());
        $failed = false;

        foreach ($pages as $page) {
            $reference = $dataset->referenceFile($page);
            $imageData = $pdf->output(["page" => $page]);
            $matches = $this->compareImages($reference->getPathname(), $imageData);

            if (!$matches) {
                $failed = true;
                $path = $reference->getPath();
                $basename = $reference->getBasename(".png");
                $failPath = "$path/$basename.fail.png";
                file_put_contents($failPath, $imageData);
            }
        }

        $this->assertFalse($failed, "Output does not match reference rendering");
    }
}
