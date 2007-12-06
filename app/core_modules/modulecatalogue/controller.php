<?php
/**
 * This file houses modulecatalogue controller class.
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   CVS: $Id$
 * @link      http://avoir.uwc.ac.za
 */


/**
 * The modulecatalogue class extends the controller class and as such is the controller
 * for the modulecatalogue module. The main fucntions of this module are module administration
 * with a catalogue interface. Allows installation and Un-installation of modules
 * via a cagtalogue interface which groups similar modules. Also incorporates module patching.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   CVS: $Id$
 * @link      http://avoir.uwc.ac.za
 */

class modulecatalogue extends controller
{
    /**
     * Object to connect to Module Catalogue table
     *
     * @var object $objDBModCat
     */
    protected $objDBModCat;

    /**
     * Object to read module information from register files
     *
     * @var object $objModFile
     */
    protected $objModFile;

    /**
     * Object to read catalogue configuration
     *
     * @var object $objCatalogueConfig
     */
    protected $objCatalogueConfig;

    /**
     * Side menu object
     *
     * @var object $objSideMenu
     */
    public $objSideMenu;

    /**
     * Logger object to log module calls
     *
     * @var object $objLog
     */
    public $objLog;

    /**
     * User object for security
     *
     * @var object $objUser
     */
    public $objUser;

    /**
     * Language object for multilingual support
     *
     * @var object $objLanguage
     */
    public $objLanguage;

    /**
     * The site configuration object
     *
     * @var object $config
     */
    public $config;

    /**
     * object to read/write module data to database
     *
     * @var object $objModule
     */
    protected $objModule;

    /**
     * object to read/write administrative module data to database
     *
     * @var object $objModuleAdmin
     */
    protected $objModuleAdmin;

    /**
     * object to check system configuration
     *
     * @var object $objSysConfig
     */
    protected $objSysConfig;

    /**
     * output varaiable to store user feedback
     *
     * @var string $output
     */
    protected $output;

    /**
     * object to manage module patches
     *
     * @var object $objPatch
     */
    protected $objPatch;
    
    public $tagCloud;
    
    protected $objTagCloud;

    /**
     * Standard initialisation function
     */
    public function init() {
        try {
            set_time_limit(0);
            $this->objRPCServer = $this->getObject('rpcserver','packages');
            $this->objRPCClient = $this->getObject('rpcclient','packages');
            $this->objUser = $this->getObject('user','security');
            $this->objConfig = $this->getObject('altconfig','config');
            $this->objLanguage = $this->getObject('language','language');
            $this->objModuleAdmin = $this->getObject('modulesadmin','modulecatalogue');
            $this->objModule = $this->getObject('modules');
            //the class for reading register.conf files
            $this->objModFile = $this->getObject('modulefile');
            $this->objPatch = $this->getObject('patch','modulecatalogue');
            $this->objCatalogueConfig = $this->getObject('catalogueconfig','modulecatalogue');
            if (!file_exists($this->objConfig->getSiteRootPath().'config/catalogue.xml')) {
                $this->objCatalogueConfig->writeCatalogue();
            }
            $this->objSideMenu = $this->getObject('catalogue','modulecatalogue');
            $this->objSideMenu->addNodes(array('updates','remote','all'));
            $sysTypes = $this->objCatalogueConfig->getCategories();
            //$xmlCat = $this->objCatalogueConfig->getNavParam('category');
            //get list of categories
            //$catArray = $xmlCat['catalogue']['category'];
            //natcasesort($catArray);
            //$this->objSideMenu->addNodes($catArray);
            $this->objSideMenu->addNodes($sysTypes);
            $this->objTagCloud = $this->getObject('tagcloud', 'utilities');
            $this->tagCloud = $this->objCatalogueConfig->getModuleTags();
            $this->processTags();
            //$this->tagCloud = $this->objTagCloud->exampletags();
            $this->objLog = $this->getObject('logactivity','logger');
            $this->objLog->log();
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * The dispatch function which handles the execution path od the module
     *
     * @return mixed template names to be displayed by the engine
     */
    public function dispatch() {
        try {
            $this->output = '';
            $action = $this->getParm('action');
            if (($action != 'firsttimeregistration') && (!$this->objUser->isAdmin())) {    //no access to non-admin users
                return 'noaccess_tpl.php';
            }
            if (!isset($activeCat)) {
                $activeCat = $this->getParm('cat','Updates');
            }
            $this->setVar('activeCat',$activeCat);
            if ($activeCat == 'remote') {
                $action = 'remote';
            }
            //$this->setVar('letter',$this->getParam('letter','none'));
            $this->setLayoutTemplate('cat_layout.php');
            $connected = $this->objRPCClient->checkConnection();
            $this->setVar('connected',$connected);
            switch ($action) {        //check action
                case 'xml':
                    $ret = $this->objRPCServer->getModuleDetails();
                    var_dump($ret); die;
                    break;
                case 'updatedeps':
                    $this->updateDeps($this->getParam('modname'));
                    return $this->nextAction('list',array('cat'=>'Updates','message'=>$this->objLanguage->languageText('mod_modulecatalogue_installeddeps','modulecatalogue')));
                case null:
                case 'list':
                    if (strtolower($activeCat) == 'updates') {
                        $this->setVar('patchArray',$this->objPatch->checkModules());
                        return 'updates_tpl.php';
                    } else {
                        return 'front_tpl.php';
                    }
                case 'uninstall':
                    $error = false;
                    $mod = $this->getParm('mod');
                    if ($this->uninstallModule($mod)) {
                        $this->output = str_replace('[MODULE]',$mod,$this->objLanguage->languageText('mod_modulecatalogue_uninstallsuccess','modulecatalogue'));
                    } else {
                        if ($this->output == '') {
                            $this->output = $this->objModuleAdmin->output;
                        }
                        $error = $this->objModuleAdmin->getLastErrorCode();
                        if (!$error) $error = -1;
                    }
                    $this->setSession('output',$this->output);
                    return $this->nextAction(null,array('cat'=>$activeCat,'lastError'=>$error));
                case 'install':
                    $error = false;
                    $mod = $this->getParm('mod');
                    $regResult = $this->installModule(trim($mod));
                    if ($regResult){
                        $this->output = str_replace('[MODULE]',$mod,$this->objLanguage->languageText('mod_modulecatalogue_installsuccess','modulecatalogue'));    //success
                    } else {
                        $error = $this->objModuleAdmin->getLastErrorCode();
                        if (!$error) $error = -1;
                        if ($this->output == '') {
                            $this->output = isset($this->objModuleAdmin->output)?$this->objModuleAdmin->output:$this->objModuleAdmin->getLastError();
                        }
                    }
                    $this->setSession('output',$this->output);
                    return $this->nextAction(null,array('cat'=>$activeCat,'lastError'=>$error));
                case 'installwithdeps':
                    $error = false;
                    $mod = trim($this->getParam('mod'));
                    $regResult = $this->smartRegister($mod);
                    if ($regResult){
                        $this->output = str_replace('[MODULE]',$mod,$this->objLanguage->languageText('mod_modulecatalogue_installsuccess','modulecatalogue'));    //success
                    } else {
                        if ($this->output == '') {
                            $this->output = $this->objModuleAdmin->output;
                        }
                        $error = $this->objModuleAdmin->getLastErrorCode();
                        if (!$error) $error = -1;
                    }
                    $this->setSession('output',$this->output);
                    return $this->nextAction(null,array('cat'=>$activeCat,'lastError'=>$error));
                case 'info':
                    $filepath = $this->objModFile->findRegisterFile($this->getParm('mod'));
                    if ($filepath) { // if there were no file it would be FALSE
                        $this->registerdata=$this->objModFile->readRegisterFile($filepath);
                        if ($this->registerdata){
                            return 'info_tpl.php';
                        }
                    } else {
                        $this->setVar('output',$this->objLanguage->languageText('mod_modulecatalogue_noinfo','modulecatalogue'));
                        return 'front_tpl.php';
                    }
                case 'textelements':
                    $texts = $this->objModuleAdmin->moduleText($this->getParm('mod'));
                    $this->setVar('moduledata',$texts);
                    $this->setVar('modname',$this->getParm('mod'));
                    return 'textelements_tpl.php';
                case 'addtext':
                    $modname = $this->getParm('mod');
                    $texts = $this->objModuleAdmin->moduleText($modname,'fix');
                    $texts = $this->objModuleAdmin->moduleText($modname);
                    $this->output=$this->objModule->output;
                    $this->setVar('output',$this->output);
                    $this->setVar('moduledata',$texts);
                    $this->setVar('modname',$modname);
                    return 'textelements_tpl.php';
                case 'replacetext':
                    $modname = $this->getParm('mod');
                    $texts=$this->objModuleAdmin->moduleText($modname,'replace');
                    $texts=$this->objModuleAdmin->moduleText($modname);
                    $this->output=$this->objModule->output;
                    $this->setVar('output',$this->output);
                    $this->setVar('moduledata',$texts);
                    $this->setVar('modname',$modname);
                    return 'textelements_tpl.php';
                case 'batchinstall':
                    $error = false;
                    $selectedModules=$this->getArrayParam('arrayList');
                    if (count($selectedModules)>0) {
                        if (!$this->batchRegister($selectedModules)) {
                            $error = -1;
                            if (!$this->output) $this->output = $this->objModuleAdmin->output;
                        }
                    } else {
                        $error = -2;
                        $this->output ='<b>'.$this->objLanguage->languageText('mod_modulecatalogue_noselect','modulecatalogue').'</b>';
                    }
                    $this->setSession('output',$this->output);
                    return $this->nextAction(null,array('cat'=>$activeCat,'lastError'=>$error));
                case 'batchuninstall':
                    $error = false;
                    $selectedModules=$this->getArrayParam('arrayList');
                    if (count($selectedModules)>0) {
                        if (!$this->batchDeregister($selectedModules)) {
                            $error = -1;
                            if (!$this->output) $this->output = $this->objModuleAdmin->output;
                        }
                    } else {
                        $error = -2;
                        $this->output ='<b>'.$this->objLanguage->languageText('mod_modulecatalogue_noselect','modulecatalogue').'</b>';
                    }
                    $this->setSession('output',$this->output);
                    return $this->nextAction(null,array('cat'=>$activeCat,'lastError'=>$error));

                case 'updateall':
                    ini_set('max_execution_time','6000');
                    set_time_limit(0);
                    $this->objModuleAdmin->updateAllText();
                    return $this->nextAction('list');

                case 'firsttimeregistration':
                    $this->objSysConfig = $this->getObject('dbsysconfig','sysconfig');
                    $sysType = $this->getParam('sysType','Basic System Only');
                    $check = $this->objSysConfig->getValue('firstreg_run','modulecatalogue');
                    if (!$check){
                        log_debug('Modulecatalogue controller - performing first time registration');
                        $this->firstRegister($sysType);
                        log_debug('First time registration complete');
                        //the config object
                                $this->objConfig = $this->getObject('altconfig','config');
                                //the lucene document object
                                $this->doc = $this->getObject('doc', 'lucene');
                                   //lucene indexing object
                                $this->index = $this->getObject('indexer','lucene');
                        log_debug('Creating the initial Lucene index');
                        //set the path to index
                                    $this->index->indexPath = $this->objConfig->getcontentBasePath();
                                    $this->indexPath = $this->index->indexPath;
                                    //do the indexing - note this indexes an ENTIRE tree, not a single doc
                                            $this->index->doIndex($this->doc);
                        log_debug('done creating Lucene index');


                    } else {
                        log_debug('First time registration has already been performed on this system. Aborting');
                    }

                     $url = array('username'=>'admin','password'=>'a','mod'=>'modulecatalogue');
                    return $this->nextAction('login',$url,'security');

                case 'update':
                    $modname = $this->getParam('mod');
                    if (($this->output = $this->objPatch->applyUpdates($modname))===FALSE) {
                        $this->setVar('error',str_replace('[MODULE]',$modname,$this->objLanguage->languageText('mod_modulecatalogue_failed','modulecatalogue')));
                    } else {
                        $this->setVar('output',$this->output);
                    }
                    $this->setVar('patchArray',$this->objPatch->checkModules());
                    return 'updates_tpl.php';

                case 'patchall':
                    $mods = $this->objPatch->checkModules();
                    $this->output = array();
                    $error = '';
                    foreach ($mods as $mod) {
                        $success = true;
                        if (($this->output[] = $this->objPatch->applyUpdates($mod['module_id'])) === FALSE) {
                            $success = false;
                            $error .= str_replace('[MODULE]',$mod['module_id'],$this->objLanguage->languageText('mod_modulecatalogue_failed','modulecatalogue'))."<br />";
                        }
                    }
                    //var_dump($error);
                    //var_dump($this->output);
                    if (!$success) {
                        $this->setVar('error',$error);
                    }
                    $this->setVar('output',$this->output);
                    $this->setVar('patchArray',$this->objPatch->checkModules());
                    return 'updates_tpl.php';

                case 'makepatch':
                    return 'makepatch_tpl.php';

                case 'reloaddefaultdata':
                    $moduleId = $this->getParam('moduleid');
                    $this->objModuleAdmin->loadData($moduleId);
                    return $this->nextAction('list',array('cat'=>$activeCat));

                case 'search':
                    $str = $this->getParam('srchstr');
                    $type = $this->getParam('srchtype');
                    $result = $this->objCatalogueConfig->searchModuleList($str,$type);
                    $this->setVar('result',$result);
                    return 'front_tpl.php';

                case 'updatexml':
                    $this->objCatalogueConfig->writeCatalogue();
                    return $this->nextAction(null,array('message' => $this->objLanguage->languageText('mod_modulecatalogue_xmlupdated','modulecatalogue')));

                case 'remote':
                    //$modules = $this->objRPCClient->getModuleList();
                    $s = microtime(true);
                    $modules = $this->objRPCClient->getModuleDetails();
                    $doc = simplexml_load_string($modules);
                    $count = count($doc->array->data->value);
    		 		$i = 0;
    		 		while($i <= $count)
    		 		{
    		 			$modobj = $doc->array->data->value[$i];
    		 			if(is_object($modobj))
    		 			{
    		 				$modulesarray[$i]['id'] = (string)$modobj->array->data->value[0]->string;
    		 				$modulesarray[$i]['name'] = (string)$modobj->array->data->value[1]->string;
    		 				$modulesarray[$i]['desc'] = (string)$modobj->array->data->value[2]->string;
    		 			}
    		 			$i++;
    		 		}
    		 		$this->setVarByRef('modules',$modulesarray);
    		 		$t = microtime(true) - $s;
    		 		log_debug ("Web service discovered $count modules in $t seconds");
    		 		//echo $t."<br />";
                    return 'remote_tpl.php';

                case 'ajaxdownload':
                    $start = microtime(true);
                    $modName = $this->getParam('moduleId');
                    if (!file_exists("$modName.zip")) {
                        if (!$encodedZip = $this->objRPCClient->getModuleZip($modName)) {
                            header('HTTP/1.0 500 Internal Server Error');
                            echo $this->objLanguage->languageText('mod_modulecatalogue_rpcerror','modulecatalogue');
                            break;
                        }
                        if (!$zipContents = base64_decode(strip_tags($encodedZip))) {
                            header('HTTP/1.0 500 Internal Server Error');
                            echo $this->objLanguage->languageText('mod_modulecatalogue_rpcerror','modulecatalogue');
                            break;
                        }
                        if (!$fh = fopen("$modName.zip",'wb')) {
                            header('HTTP/1.0 500 Internal Server Error');
                            echo $this->objLanguage->languageText('mod_modulecatalogue_fileerror','modulecatalogue');
                            break;
                        }
                        if (!fwrite($fh,$zipContents)) {
                            header('HTTP/1.0 500 Internal Server Error');
                            echo $this->objLanguage->languageText('mod_modulecatalogue_fileerror','modulecatalogue');
                            break;
                        }
                        fclose($fh);
                    }
                    echo $this->objLanguage->languageText('phrase_unzipping');
                    break;

                case 'ajaxunzip':
                    $modName = $this->getParam('moduleId');
                    if (!is_dir($this->objConfig->getModulePath().$modName)) {
                        $objZip = $this->getObject('wzip', 'utilities');
                        if (!$objZip->unZipArchive("$modName.zip", $this->objConfig->getModulePath())) {
                            header('HTTP/1.0 500 Internal Server Error');
                            echo $this->objLanguage->languageText('mod_modulecatalogue_unziperror','modulecatalogue');
                            echo "<br /> $objZip->error";
                            break;
                        }
                    }
                    echo $this->objLanguage->languageText('phrase_installing');
                    break;

                case 'ajaxinstall':
                    $modName = $this->getParam('moduleId');
                    if (!$this->installModule($modName)) {
                        header('HTTP/1.0 500 Internal Server Error');
                        echo "$this->output\n{$this->objModuleAdmin->output}";
                        break;
                    }
                    unlink("$modName.zip");
                    echo "<b>".$this->objLanguage->languageText('word_installed')."</b>";
                    break;

                case 'uploadarchive':
                    $file = $_FILES['archive']['name'];
                    $module = substr($file,0,strpos($file,'.'));
                    $tmpFile = $_FILES['archive']['tmp_name'];
                    var_dump($_FILES);
                    if ($_FILES['archive']['size'] == 0) {
                        $this->setSession("output",$this->objLanguage->languageText('mod_modulecatalogue_notfound','modulecatalogue'));
                        $this->setVar('error',1);
                        return 'front_tpl.php';
                    }
                    if (is_dir($this->objConfig->getModulePath().$module)) {
                        $this->setSession('output',$this->objLanguage->languageText('mod_modulecatalogue_directoryexists','modulecatalogue'));
                        $this->setVar('error',1);
                        return 'front_tpl.php';
                    }
                    if (!file_exists($file)) {
                        $this->setSession("output",$this->objLanguage->languageText('mod_modulecatalogue_transferfailed','modulecatalogue'));
                        $this->setVar('error',1);
                        return 'front_tpl.php';
                    }
                    if (strtolower(substr($file,strlen($file)-4,4)) == '.zip') {
                        $objZip = $this->getObject('wzip', 'utilities');
                        if (!$objZip->unZipArchive($tmpFile, $this->objConfig->getModulePath())) {
                            $this->setSession("output",$this->objLanguage->languageText('mod_modulecatalogue_unziperror','modulecatalogue')."<br /> $objZip->error");
                            $this->setVar('error',1);
                        return 'front_tpl.php';
                        }
                    } else {
                        require_once($this->getPearResource('Archive/Tar.php'));
                        $objArchive = new Archive_Tar($tmpFile);
                        if (!$objArchive->extract($this->objConfig->getModulePath())) {
                            $this->setSession("output",$this->objLanguage->languageText('mod_modulecatalogue_untarerror','modulecatalogue'));
                            $this->setVar('error',1);
                        return 'front_tpl.php';
                        }
                    }
                    return $this->nextAction('install',array('cat'=>$activeCat,'mod'=>$module));

                default:
                    throw new customException($this->objLanguage->languageText('mod_modulecatalogue_unknownaction','modulecatalogue').': '.$action);
                    break;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This method is a 'wrapper' function - it takes info from the
    * 'register.conf' file provided by the module to be registered,
    * and passes it to its namesake function in the modulesadmin
    * class - which is where the SQL entries actually happen.
    * @author James Scoble
    * @param  string $modname the module_id of the module to be used
    * @return string $regResult
    */
    private function installModule($modname) {
        try {
            $filepath = $this->objModFile->findRegisterFile($modname);
            if ($filepath) { // if there were no file it would be FALSE
                $this->registerdata=$this->objModFile->readRegisterFile($filepath);
                if ($this->registerdata) {
                    // Added 2005-08-24 as extra check
                    if ( isset($this->registerdata['WARNING']) && ($this->getParm('confirm')!='1') ){
                        $this->output = $this->registerdata['WARNING'];
                        return FALSE;
                    }
                    // var_dump($this->registerdata); die();
                    return $this->objModuleAdmin->installModule($this->registerdata);
                }
            } else {
                $this->output = $this->objLanguage->languageText('mod_modulecatalogue_errnofile','modulecatalogue');
                return FALSE;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    } // end of function

     /**
    * This method is a 'wrapper' function - it takes info from the 'register.conf'
    * file provided by the module to be registered, and passes it to its namesake
    * function in the modulesadmin class - which is where the SQL entries actually
    * happen. It uses file() to load the register.php file into an array, then
    * chew through it line by line, looking for keywords.
    *
    * @author  James Scoble
    * @param   string $modname the module_id of the module to be used
    * @returns boolean TRUE or FALSE
    */
    private function uninstallModule($modname) {
        try {
            $filepath=$this->objModFile->findRegisterFile($modname);
            $this->registerdata=$this->objModFile->readRegisterFile($filepath);
            if (is_array($this->registerdata)) {
                return $this->objModuleAdmin->uninstallModule($modname,$this->registerdata);
            } else {
                $this->output = $this->objLanguage->languageText('mod_modulecatalogue_errnofile','modulecatalogue');
                return FALSE;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Method to handle registration of multiple modules at once
    * @param array $modArray
    */
    private function batchRegister($modArray) {
        try {
            foreach ($modArray as $line) {
                if ($line != 'on') {
                    if (!$this->smartRegister($line)) {
                        //$this->output = str_replace('[MODULE]',$line,$this->objLanguage->languageText('mod_modulecatalogue_failed','modulecatalogue'));
                        return FALSE;
                    }
                }
            }
            return TRUE;
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This method is designed to handle the registeration of multiple modules at once.
    * @param string $modname
    */
    private function smartRegister($modname) {
        try {
            $isReg = $this->objModule->checkIfRegistered($modname,$modname);
            if ($isReg){
                return TRUE;
            }
            $filepath = $this->objModFile->findRegisterFile($modname);
            if ($filepath) { //if there were no file it would be FALSE
                $registerdata=$this->objModFile->readRegisterFile($filepath);
                if ($registerdata){
                    if (isset($registerdata['DEPENDS'])){
                        foreach ($registerdata['DEPENDS'] as $line) {
                            $result=$this->smartRegister($line);
                            if ($result==FALSE) {
                                $this->output = $this->objModuleAdmin->output."\n";
                                $this->output .= str_replace('{MODULE}',$line,$this->objLanguage->languageText('mod_modulecatalogue_needmodule','modulecatalogue'))."\n";
                                return FALSE;
                            }
                        }
                    }
                    $regResult= $this->objModuleAdmin->installModule($registerdata);
                    if ($regResult){
                        $this->output[] = str_replace('[MODULE]',$modname,$this->objLanguage->languageText('mod_modulecatalogue_regconfirm','modulecatalogue'));
                    }
                    return $regResult;
                }
            } else {
                $this->output .= $this->objLanguage->languageText('mod_modulecatalogue_errnofile','modulecatalogue')."\n";
                return FALSE;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * Method to handle deregistration of multiple modules at once
    * @param array $modArray
    */
    private function batchDeregister($modArray) {
        try {
            foreach ($modArray as $line) {
                if (!$this->smartDeregister($line)) {
                    return false;
                }
            }
            return TRUE;
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
    * This method is designed to handle the deregisteration of multiple modules at once.
    * @param string $modname
    */
    private function smartDeregister($modname) {
        try {
            $isReg=$this->objModule->checkIfRegistered($modname,$modname);
            if ($isReg==FALSE){
                return TRUE;
            }
            $filepath=$this->objModFile->findRegisterFile($modname);
            if ($filepath) { // if there were no file it would be FALSE
                $registerdata=$this->objModFile->readRegisterFile($filepath);
                if ($registerdata) {
                    // Here we get a list of modules that depend on this one
                    $depending=$this->objModule->getDependencies($modname);
                    if (count($depending)>0) {
                        foreach ($depending as $line) {
                            $result=$this->smartDeregister($line);
                            if ($result==FALSE) {
                                return FALSE;
                            }
                        }
                    }
                    $regResult= $this->objModuleAdmin->uninstallModule($modname,$registerdata);
                    if ($regResult) {
                        $this->output[] = str_replace('[MODULE]',$modname,$this->objLanguage->languageText('mod_modulecatalogue_deregconfirm','modulecatalogue'));
                    }
                    return $regResult;
                }
            } else {
                $this->output = $this->objLanguage->languageText('mod_modulecatalogue_errnofile','modulecatalogue');
                return FALSE;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * Method to install newly added depenedencies of a module
     *
     * @param string $moduleId the module whose dependencies must be updated
     */
    private function updateDeps($moduleId) {
        $rData = $this->objModFile->readRegisterFile($this->objModFile->findRegisterFile($moduleId));
        foreach ($rData['DEPENDS'] as $dep) {
            if (!$this->smartRegister(trim($dep))) {
                throw new customException("Error installing dependency $dep: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}");
            }
        }
    }

    /**
    * This is a method to handle first-time registration of the basic modules
    *
    * @param string sysType The type of system to install
    */
    private function firstRegister($sysType) {
        try {
            log_debug("Installing system, type: $sysType");
            $root = $this->objConfig->getsiteRootPath();
            if (!file_exists($root.'config/config.xml')){
                throw new customException("could not find config.xml! tried {$root}config/config.xml");
            }
            if (!file_exists($root.'installer/dbhandlers/systemtypes.xml')){
                throw new customException("could not find systemtypes.xml! tried {$root}installer/dbhandlers/default_modules.txt");
            }
            $objXml = simplexml_load_file($root.'installer/dbhandlers/systemtypes.xml');
            log_debug('Installing core modules');
            $coreList = $objXml->xpath("//category[categoryname='Basic System Only']");
            foreach ($coreList[0]->module as $module) {
                if (!$this->smartRegister(trim($module))) {
                    throw new customException("Error installing module $module: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}");
                }
            }
            if ($sysType != "Basic System Only") {
                log_debug('Installing system specific modules');
                $specificList = $objXml->xpath("//category[categoryname='$sysType']");
                foreach ($specificList[0]->module as $module) {
                    if (!$this->smartRegister(trim($module))) {
                        throw new customException("Error installing module $module: {$this->objModuleAdmin->output} {$this->objModuleAdmin->getLastError()}");
                    }
                }
            }
            // Flag the first time registration as having been run
            $this->objSysConfig->insertParam('firstreg_run','modulecatalogue',TRUE,'mod_modulecatalogue_firstreg_run_desc');
            log_debug('first time registration performed, variable set. First time registration cannot be performed again unless system variable \'firstreg_run\' is unset.');

        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $exception
     * @return void
     */
    public function errorCallback($exception) {
        echo customException::cleanUp($exception);
    }

    /**
     * Method to determine whether the module requires the user to be logged in.
     *
     * @return TRUE|FALSE false if the user is carrying out first time module registration, else true.
     */
    public function requiresLogin() {
        try {
            if ($this->getParm('action') == 'firsttimeregistration') {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }

    /**
     * kind of a hack wrapper method to get the messed up params from the header via getParam in the engine
     *
     * @param  string $name parameter name
     * @param  string $def  default param value
     * @return string Parameter value or default if it doesnt exist
     */
    public function getParm($name,$def=null) {
        try {
            if (($res = $this->getParam($name)) == null) {
                return $this->getParam('amp;'.$name,$def);
            } else {
                return $res;
            }
        } catch (customException $e) {
            $this->errorCallback('Caught exception: '.$e->getMessage());
            exit();
        }
    }
    
    public function processTags()
    {
    	
    	foreach($this->tagCloud as $arrs)
    	{
    		if(!empty($arrs['tags']))
    		{
    			$arrs['tags'] = explode(',', ereg_replace(' +', '', $arrs['tags']));
    		}
    		$tagarr[] = $arrs;
    	}
    	//var_dump($tagarr); die();
    }
}
?>