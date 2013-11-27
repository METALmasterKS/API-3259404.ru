<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */
namespace PriceParse\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter;

class UploadForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);
        $this->addElements();
        $this->addInputFilter();
    }

    public function addElements()
    {
        // File Input
        $file = new Element\File('excel-file');
        $file->setLabel('excel data uploader')
            ->setAttribute('id', 'excel-file');
        $this->add($file);

        $email=new Element\Email('admin-email');
        $email->setLabel('admin email')
             ->setAttribute('id','admin-email');
        $this->add($email);

    }
    public function addInputFilter()
    {

        $inputFilter = new InputFilter\InputFilter();

        //Email Input
        $emailInput=new InputFilter\Input('admin-email');
        $emailInput->getValidatorChain()
            ->attachByName('inArray', array('haystack'=>array('007@3259404.ru','metalmaster.kustovstas@gmail.com','rhenium.osmium@gmail.com')));//стек из допустимых email адресов
        $inputFilter->add($emailInput);

        // File Input
        $fileInput = new InputFilter\FileInput('excel-file');
        $fileInput->setRequired(true);


        $fileInput->getValidatorChain()
            ->attachByName('filesize',      array('max' => 100000));//1М макс



        $inputFilter->add($fileInput);

        $this->setInputFilter($inputFilter);
    }
}