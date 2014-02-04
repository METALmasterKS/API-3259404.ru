<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */
namespace PriceParse\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter;
use Zend\Validator\File\Extension;

class UploadCronForm extends Form
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
        $file = new Element\File('txt-file-for-crone');
        $file->setLabel('file data uploader for a cron')
            ->setAttribute('id', 'txt-file-for-crone');
        $this->add($file);
    }

    public function addInputFilter()
    {

        $inputFilter = new InputFilter\InputFilter();
        // File Input
        $fileInput = new InputFilter\FileInput('txt-file-for-crone');
        $fileInput->setRequired(true);


        $fileInput->getValidatorChain()
            ->attach(new Extension(array('txt')))
            ->attachByName('filesize',array('max' => 10000000));//10Мегабайт макс




        $inputFilter->add($fileInput);

        $this->setInputFilter($inputFilter);
    }
}