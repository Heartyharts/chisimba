<?php

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check
/**
 * The class that is used for compression
 *
 * @category  Chisimba
 * @package utilities
 * @author Wesley Nitsckie
 * @copyright 2004, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
 * @version $Id$
 * @link      http://avoir.uwc.ac.za
 */

require_once('pclzip.lib.php');
class wzip extends object{

    /**
    * @var stores an error
    * @access public
    *
    */
    public $error;

    /**
    * Constructor
    */
    function init(){

    }

    /**
    * Method used to inflate a compressed file, with return values
    * @author Nic Appleby
    * @param string $filename The path to the file
    * @param string $path The path to which the file will be unzipped
    * @return true|false
    */
    function unZipArchive($filename,$path){
        $this->error = null;
        //if we are going to turn of reporting notices we should
        //put things back the way they were afterwards
        $error_reporting = ini_get('error_reporting');
        ini_set('error_reporting', 'E_ALL & ~E_NOTICE');

        //create a new instance of pclzip
        $archive = new PclZip($filename);
        if ($archive->extract(PCLZIP_OPT_PATH,$path,PCLZIP_OPT_REMOVE_PATH,'install/release') == 0) {
            $ret = false;
            $this->error = "Error : ".$archive->errorInfo(true);
        } else {
            $ret = true;
        }
        ini_set('error_reporting',$error_reporting);
        return $ret;
    }

    /**
    * Method used to deflate a compressed file
    * @param string $filename The path to the file
    * @param string $path The path to which the file will be unzipped
    * @return null
    * @deprecated Terrible error handling, rather use the method above
    */
    function unzip($filename,$path){
        // turn of reporting notices
        ini_set('error_reporting', 'E_ALL& ~E_NOTICE');

        //create a new instance of pclzip
        $archive = new PclZip($filename);

        //extract the file
        //$objZip->extract($path);
        if ($archive->extract(PCLZIP_OPT_PATH, $path,
                        PCLZIP_OPT_REMOVE_PATH, 'install/release') == 0) {
            print ("Error : ".$archive->errorInfo(true));
        }
    }




    /**
    * MEthod to add files to an archive
    * @param string $filename The path to the file
    * @param string $path The path to which the file will be unzipped
    * @return null
    */
    function addArchive($path, $filename, $removePath = NULL)
    {

        $archive = new PclZip($filename);
        $v_list = $archive->create($path, PCLZIP_OPT_REMOVE_PATH, $removePath);
          if ($v_list == 0) {
            die("Error : ".$archive->errorInfo(true));
          }

          return $filename;

    }

    /**
    * Method to get the list of files in an archive
    * @param string path to zip file
    * @return array list of files
    */
    public function listArchiveFiles($path)
    {
        $zip = new PclZip($path);

        if (($list = $zip->listContent()) == 0) {
            return FALSE;
        } else {
            return $list;
        }
    }
}
?>