<?php

namespace App;

class ExcelBuilder
{
    private $header = [];
    private $data = [];
    private $excel;

    public function __construct($header, $data)
    {
        $this->header = $header;
        $this->data = $data;        
    }

    public function build()
    {
        $header = $this->header;
        $data = $this->data;
        $this->excel = \Excel::create('Consolidado', function($excel) use ($header, $data) {
            $excel->sheet('1', function($sheet) use ($header, $data) {
                $sheet->row(1, $header);
                $sheet->rows($data);
            });
        });
    }

    public function get()
    {
        return $this->excel->export('xlsx', ['Access-Control-Allow-Origin'=>'*']);
    }
}