<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"> 
<?php

$cssLayout = & $this->newObject('csslayout', 'htmlelements');// Set columns to 2
$cssLayout->setNumColumns(3);

$nav = $this->getObject('nav', 'ocsinterface');
$leftSideColumn = $nav->getLeftContent();
$rightSideColumn=$nav->getRightContent();

$cssLayout->setLeftColumnContent($leftSideColumn);
$cssLayout->setRightColumnContent($rightSideColumn);

//$middleSideColumn='<h1>'.$coursedata['title'].'</h1>';
$middleSideColumn='<div style="margin-left:50px; margin-right:30px;padding:10px;">';
$objWashout = $this->getObject('washout', 'utilities');

//Add the table to the centered layer
$middleSideColumn .= $objWashout->parseText($this->objViewerUtils->getContent($storyid));
$middleSideColumn.= '</div>';
// Add Right Column
$cssLayout->setMiddleColumnContent($middleSideColumn);
echo $cssLayout->show();
?>
