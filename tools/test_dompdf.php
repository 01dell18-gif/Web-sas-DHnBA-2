<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';
}
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml('<h1>Halo dari Dompdf!</h1>');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$output = $dompdf->output();
echo "Dompdf PDF generated successfully! Length: " . strlen($output) . " bytes\n";
