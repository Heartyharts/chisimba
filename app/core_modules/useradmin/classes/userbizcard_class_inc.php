<?php

/**
* User Business Card Generator
* 
* This class displays user details as a business class
* @author Tohir Solomons
*/
class userbizcard extends object
{

    /**
    * @var Array $userArray User Details stored as an array
    * @access private
    */
    private $userArray;
    
    /**
    * @var string $backgroundColor Background Color of the Business Card
    * @access public
    */
    public $backgroundColor='#EAEAEA';
    
    /**
    * @var boolean $showResetImage A flag on whether to show the reset button if user has a custom User Image
    * @access public
    */
    public $showResetImage=FALSE;
    
    /**
    * @var string $resetModule Module to go to reset Image
    * @access public
    */
    public $resetModule = 'useradmin';
    
    /**
    * @var string $resetAction Action in Module to reset Image
    * @access public
    */
    public $resetAction = 'resetimage';
    
    /**
    * Constructor
    */
    public function init()
    {
        $this->objUser = $this->getObject('user', 'security');
        $this->objCountries = $this->getObject('countries', 'utilities');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->loadClass('hiddeninput', 'htmlelements');
        $this->loadClass('htmlheading', 'htmlelements');
        $this->loadClass('layer', 'htmlelements');
        $this->loadClass('htmltable', 'htmlelements');
        $this->loadClass('fieldset', 'htmlelements');
    }
    
    /**
    * Method to pass the user details as an array to the class
    * @param array $userDetails
    */
    public function setUserArray($userDetails)
    {
        $this->userArray = $userDetails;
    }
    
    /**
    * Method to Display the Business Card
    * @return string
    */
    public function show()
    {
        switch ($this->userArray['sex'])
        {
            case 'M': $gender = 'Male'; break;
            case 'F': $gender = 'Female'; break;
            default : $gender = 'Unknown'; break;
        }
        
        $emailLabel = $this->objLanguage->languageText('phrase_emailaddress');
        $genderLabel = $this->objLanguage->languageText('word_gender');
        $mobileLabel = $this->objLanguage->languageText('phrase_mobilenumber');
        $countryLabel = $this->objLanguage->languageText('word_country');
        

        $image = $this->objUser->getUserImage($this->userArray['userid'], TRUE);

        if ($this->showResetImage) {
            if ($this->objUser->hasCustomImage($this->userArray['userid'])) {
                $resetimageform = new form('updateimage', $this->uri(array('action'=>$this->resetAction), $this->resetModule));
                
                $id = new hiddeninput('id', $this->userArray['id']);
                $resetimageform->addToForm($id->show());
                
                $userid = new hiddeninput('userid', $this->userArray['userid']);
                $resetimageform->addToForm($userid->show());
                
                $button = new button ('resetimage', 'Reset Image');
                $button->setToSubmit();
                $resetimageform->addToForm(' '.$button->show());
                $image .= $resetimageform->show();
            }
        }
        
        $objHeading = new htmlheading();
        $objHeading->str = $this->userArray['title'].' '.$this->userArray['firstname'].' '.$this->userArray['surname'];
        $objHeading->type = 1;
        $heading = $objHeading->show();
        
        $objTable = new htmltable();        
        $objTable->width = '100%';
        $objTable->cellpadding = '4';
        $objTable->startRow();
        $objTable->addCell($image, '25%', 'center', 'center', 'userbizcardleft', 'rowspan="6"');
        $objTable->endRow();
        $objTable->startRow();
        $objTable->addCell('<strong>'.ucfirst(strtolower($emailLabel)).':</strong>', '30%', '', '', 'heading', '');
        $objTable->addCell($this->userArray['emailaddress'], '', '', '', 'heading', '');
        $objTable->endRow();
        $objTable->startRow();
        $objTable->addCell('<strong>'.$mobileLabel.':</strong>', '', '', '', 'heading', '');
        $objTable->addCell($this->userArray['cellnumber'], '', '', '', 'heading', '');
        $objTable->endRow();
        $objTable->startRow();
        $objTable->addCell('<strong>'.$countryLabel.':</strong>', '', '', '', 'heading', '');
        $objTable->addCell($this->objCountries->getCountryName($this->userArray['country']).' '.$this->objCountries->getCountryFlag($this->userArray['country']), '', '', '', 'heading', '');
        $objTable->endRow();
        $objTable->startRow();
        $objTable->addCell('<strong>'.$genderLabel.':</strong>', '', '', '', 'heading', '');
        $objTable->addCell($gender, '', '', '', 'heading', '');
        $objTable->endRow();
        $string = $objTable->show();
        
        $objFieldset = new fieldset();
        $objFieldset->legend = $heading;
        $objFieldset->contents =  $string;
        $objFieldset->width = '500px';
        $fieldset = $objFieldset->show();

        return $fieldset;
    }

}

?>