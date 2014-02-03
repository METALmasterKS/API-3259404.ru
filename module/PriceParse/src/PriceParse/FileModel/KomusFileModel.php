<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 16.12.13
 */

namespace PriceParse\FileModel;


class KomusFileModel {

  public $fileModel;
    public $status;
    public static $komusSwitcher;
    public function __construct($adminModelInstance){
        self::$komusSwitcher='on';
        $this->fileModel=new FileModel($adminModelInstance,'komus');
        $this->status=$this->fileModel->status;
       // return $this->fileModel;
    }




    /**
     * Метод читает загруженный KomusTxt файл -три колонки в txt файле разделенные табуляцией,преобразует в массив PHP
     *
     * @param $inputFileName
     * @param $columnWidth
     *
     * @return array
     */
    public static function komusTxtDataToTwoDimensionalArray($inputFileName,$columnWidth){

        $arrayFromFile=self::getCsv($inputFileName,$columnWidth);
        $commonResultArray= array_chunk($arrayFromFile,$columnWidth);
        $arrayToResult=array();
        foreach($commonResultArray as $block){
            $arrayToResult[]=array($block);
        }
        return $commonResultArray;
    }

    public  static function getCsv($inputFileName,$columnWidth){
        $arrayFromFile=array();
        if (($handle = fopen($inputFileName, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {

                for ($c=0; $c < $columnWidth; $c++) {
                    $arrayFromFile[]= $data[$c];
                }
            }
            fclose($handle);
        }
        return $arrayFromFile;
    }


    /**
     * Метод читает загруженный KomusTxt файл который сохранен в txt из Excel,преобразует в массив PHP
     *
     * @param $inputFileName    имя txt файла
     *
     * @return array     двумерный массив из txt файла
     */
    public function komusTxtDataConvertFromExcelToTwoDimensionalArray($inputFileName){

        $arrayFromFile=self::getCsv($inputFileName,8);
        $dirtyArrayBlocks=array_chunk(array_slice($arrayFromFile,16), 8);
        return self::komusClearArrayFromEmptyStock($dirtyArrayBlocks);
    }

    /**
     * Метод очищает полученнный массив из komus-txt файла от блоков содержащих разделы(в xls голубые полоски)
     *
     * @param array $dirtyArrayBlocks
     *
     * @return array
     */
    public static function komusClearArrayFromEmptyStock(array $dirtyArrayBlocks){
        $clearArrayBlocks=array();
        foreach($dirtyArrayBlocks as $block){
            if(in_array("",$block)) continue;
            $clearArrayBlocks[]=$block;

        }
        return $clearArrayBlocks;
    }


} 