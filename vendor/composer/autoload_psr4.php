<?php

// autoload_psr4.php for root vendor indexing
$vendorDir = __DIR__ . '/../dompdf/vendor';

return array(
    'Svg\\' => array($vendorDir . '/dompdf/php-svg-lib/src/Svg'),
    'Sabberworm\\CSS\\' => array($vendorDir . '/sabberworm/php-css-parser/src'),
    'Masterminds\\' => array($vendorDir . '/masterminds/html5/src'),
    'FontLib\\' => array($vendorDir . '/dompdf/php-font-lib/src/FontLib'),
    'Dompdf\\' => array($vendorDir . '/dompdf/dompdf/src'),
);
