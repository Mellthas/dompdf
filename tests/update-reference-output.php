<?php
use Dompdf\Tests\OutputTest;
use Dompdf\Tests\OutputTest\Dataset;

require __DIR__ . "/../vendor/autoload.php";

$args = [$argv[1] ?? "", $argv[2] ?? ""];
$optionIndex = substr($args[0], 0, 2) === "--" ? 0 : 1;
$pathIndex = substr($args[0], 0, 2) === "--" ? 1 : 0;
$outputPdf = empty($args[$optionIndex]) || $args[$optionIndex] === "--pdf";
$outputImage = empty($args[$optionIndex]) || $args[$optionIndex] === "--png";
$pathTest = $args[$pathIndex];
$datasets = OutputTest::datasets();
$include = !empty($pathTest)
    ? function (Dataset $set) use ($pathTest) {
        return substr($set->name(), 0, strlen($pathTest)) === $pathTest;
    } : function ($set) { return true; };

foreach ($datasets as $dataset) {
    if (!$include($dataset)) {
        continue;
    }

    echo "Updating " . $dataset->name();

    if ($outputImage) {
        echo " (PNG)";
        $dataset->outputImage();
    }

    if ($outputPdf) {
        echo " (PDF)";
        $dataset->outputPdf();
    }

    echo PHP_EOL;
}
