<?php

namespace tieume\Lang\Helpers;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class LangHelper
{
    /**
     * @param string $path
     * @param bool $replaceView
     * @param bool $reverse
     */
    public function generate(string $path, bool $replaceView, bool $reverse)
    {
        if (is_dir($path)) {
            foreach (glob($path . '/*.*') as $file) {
                $this->generate($file, $replaceView, $reverse);
            };
        } else {
            $file = basename($path);
            $filename = substr($file, 0, strpos($file, '.'));
            $resourcePath = resource_path('lang');
            if ($filename) {
                $tab = '    ';
                $startStr = '<?php ' . PHP_EOL . $tab . 'return [ ' . PHP_EOL;
                $endStr = $tab . '];';
                $rowStr = $tab . $tab . '\'%s\' => \'%s\',' . PHP_EOL;
                $reader = new Csv();
                $reader->setLoadSheetsOnly(0);
                $spreadsheet = $reader->load($path);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $maxCol = Coordinate::columnIndexFromString($highestColumn);
                $rows = [];
                $languages = [];
                // Get languages list
                $row = 2; // Start from row index = 2
                $col = 4; // Start from column D
                $cell = $worksheet->getCellByColumnAndRow($col, 2);
                while (!empty($cell->getValue())) {
                    $languages[$col] = $cell->getValue();
                    $col++;
                    $cell = $worksheet->getCellByColumnAndRow($col, 2);
                }
                // Start generate
                extract(array_values($languages));
                foreach ($languages as $langCol => $language) {
                    $col = 3; // Start from column C
                    $content = '';
                    for ($row = 3; $row <= $highestRow; $row++) {
                        if ($label = $worksheet->getCellByColumnAndRow($col, $row)->getValue()) {
                            if ($replaceView) {
                                $bladePath = $worksheet->getCellByColumnAndRow($maxCol, $row)->getValue();
                                if (!empty($bladePath)) {
                                    $bladeFilePath = resource_path('/') . DIRECTORY_SEPARATOR . $bladePath;
                                    try {
                                        $bladeContent = file_get_contents($bladeFilePath);
                                        file_put_contents($bladeFilePath, str_replace(
                                            '>' . $worksheet->getCellByColumnAndRow($langCol, $row) . '<'
                                            , '>{{__(\'' . $filename . '.' . $label . '\')}}<'
                                            , $bladeContent));
                                    } catch (\Exception $e) {
                                    }
                                }
                            }
                            $content .= sprintf($rowStr, $label, htmlspecialchars($worksheet->getCellByColumnAndRow($langCol, $row)->getValue(), ENT_QUOTES));
                        }
                    }
                    $content = $startStr . $content . $endStr;
                    try {
                        $dir = $resourcePath . DIRECTORY_SEPARATOR . $language . '/';
                        $dirJs = resource_path('js/lang') . DIRECTORY_SEPARATOR . $language . '/';

                        if (is_dir($dir) === false) {
                            mkdir($dir, 0777, true);
                        }

                        if (is_dir($dirJs) === false) {
                            mkdir($dirJs, 0777, true);
                        }

                        file_put_contents($dir . $filename . '.php', $content);
                        file_put_contents($dirJs . $filename . '.js', "const $filename = " . json_encode(trans($filename, [], $language)) . ";\n");
                        echo 'File ' . $dir . $filename . '.php' . ' created' . PHP_EOL;
                        echo 'File ' . $dirJs . $filename . '.js' . ' created' . PHP_EOL;
                    } catch (\Exception $e) {
                        dd($e);
                    }
                }
            }
        }
    }
}
