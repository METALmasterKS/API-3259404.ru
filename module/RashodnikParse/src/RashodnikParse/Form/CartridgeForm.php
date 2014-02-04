<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */


namespace RashodnikParse\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class CartridgeForm extends Form
{
    public function __construct($name,$dataFromDB){
        parent::__construct($name);

        //todo сделать для setEmptyOption value =0
        $selectBrand = new Element\Select('brand');
        $selectBrand->setEmptyOption('Выберите производителя');
        $selectBrand->setValueOptions($dataFromDB);
        $selectBrand->setAttributes(array('id'=>'brand'));

        $selectDisabledType = new Element\Select('type');
        $selectDisabledType->setEmptyOption('Выберите тип устройства');
        $selectDisabledType->setAttributes(array('disabled'=>'disabled','id'=>'type'));

        $selectDisabledModel = new Element\Select('model');
        $selectDisabledModel->setEmptyOption('Выберите  модель');
        $selectDisabledModel->setAttributes(array('disabled'=>'disabled','id'=>'model'));


        $this->add($selectBrand);
        $this->add($selectDisabledType);
        $this->add($selectDisabledModel);
        $this->add(array(
                   'name'=>'submit',

                   'attributes'=>array(
                       'value'=>'подобрать',
                       'id'=>'sendIt',
                       'type'=>'Submit',
                   ),

                   )

        );
        $this->setAttribute('onsubmit', 'return false');



    }
}
