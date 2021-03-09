<?php
namespace Dompdf\Tests\OutputTest;

use Dompdf\Options;
use Dompdf\Dompdf;
use SplFileInfo;

class Dataset
{
    /**
     * @var SplFileInfo
     */
    protected $file;

    public const DATASET_DIRECTORY = __DIR__;
    protected const DEFAULT_PAPER_SIZE = [0, 0, 400, 300];

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        $prefixLength = strlen(self::DATASET_DIRECTORY);
        $path = substr($this->file->getPath(), $prefixLength + 1);
        return $path . "/" . $this->basename();
    }

    public function basename(): string
    {
        return $this->file->getBasename("." . $this->file->getExtension());
    }

    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

    /**
     * Get the file info of the reference image for the specified page.
     *
     * @param int $page The number of the page to get.
     * @return SplFileInfo
     */
    public function referenceFile(int $page): SplFileInfo
    {
        $path = $this->file->getPath();
        $basename = $this->basename();
        return new SplFileInfo("$path/$basename.p$page.png");
    }

    public function render(string $backend): Dompdf
    {
        $options = new Options();
        $options->setPdfBackend($backend);
        $options->setDefaultPaperSize(self::DEFAULT_PAPER_SIZE);

        $pdf = new Dompdf($options);
        $pdf->loadHtmlFile($this->file->getPathname());
        $pdf->setBasePath($this->file->getPath());
        $pdf->render();

        return $pdf;
    }

    public function outputImage(): void
    {
        $pdf = $this->render("gd");
        $pages = range(1, $pdf->getCanvas()->get_page_count());

        foreach ($pages as $page) {
            $path = $this->referenceFile($page)->getPathname();
            $imageData = $pdf->output(["page" => $page]);
            file_put_contents($path, $imageData);
        }
    }

    public function outputPdf(): void
    {
        $pdf = $this->render("cpdf");
        $basename = $this->basename();
        $path = $this->file->getPath() . "/$basename.pdf";
        file_put_contents($path, $pdf->output());
    }
}
