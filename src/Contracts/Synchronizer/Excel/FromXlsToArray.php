<?php

namespace AdminEshop\Contracts\Synchronizer\Excel;

use Admin\Core\Helpers\File;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class FromXlsToArray
{
    protected $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    private function getReader()
    {
        $extension = $this->file->extension;

        if ( $extension == 'xls' ) {
            return new Xls;
        } else if ( $extension == 'xlsx' ) {
            return new Xlsx;
        } else if ( $extension == 'csv' ) {
            return new Csv;
        }
    }

    public function toArray()
    {
        $reader = $this->getReader();
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($this->file->basepath);

        $worksheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $header = [];
        $i = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getCalculatedValue();
            }

            //We want bind header
            if ( $i == 0 ) {
                foreach ($rowData as $name) {
                    $header[$this->parseHeaderString($name)] = $name;
                }

                $header = array_filter($header);
            }

            //Add row
            else {
                $trimmedRowData = array_slice($rowData, 0, count($header));

                $rows[] = array_combine(array_keys($header), $trimmedRowData);
            }

            $i++;
        }

        return compact('header', 'rows');
    }

    public function parseHeaderString($string)
    {
        $string = preg_replace("/{\s| |\.|\-|\_}/", '-', $string);
        $string = mb_strtolower($string);
        $string = str_slug($string);
        $string = str_replace('-', '_', $string);

        return $string;
    }
}
