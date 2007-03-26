<?php
/**
*
* Class to parse a string (e.g. page content) that contains a link
* to a timeline and render the timeline in the page
*
* @author Derek Keats
*
*/

class parse4timeline extends object
{
    
    public function init()
    {
        $this->objTlParser = $this->getObject('timelineparser', 'timeline');
    }
    
    /**
    *
    * Method to parse the string
    * @param String $str The string to parse
    * @return The parsed string
    *
    */
    public function parse($str)
    {
        preg_match_all('/\\[TIMELINE]<a.*?href="(?P<timelinelink>.*?)".*?>.*?<\/a>\\[\/TIMELINE]/', $str, $results, PREG_PATTERN_ORDER);
        $counter = 0;
        foreach ($results[0] as $item)
        {
            $replacement = $this->objTlParser->getRemote($results['timelinelink'][$counter]);
            $str = str_replace($item, $replacement, $str);
            $counter++;
        }
        
        return $str;
    }
}