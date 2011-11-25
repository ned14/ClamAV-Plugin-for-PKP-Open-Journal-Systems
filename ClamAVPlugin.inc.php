<?php 

/* ClamAVPlugin for PKP's OJS
(C) 2011 Niall Douglas http://www.nedprod.com/
*/


import('classes.plugins.GenericPlugin'); 
require_once('Clamd.php');
include('config.inc');

class ClamAVPlugin extends GenericPlugin { 
    function register($category, $path) { 
        if (parent::register($category, $path) ) { 
            if ($this->getEnabled()) {
                HookRegistry::register('ArticleFileManager::handleUpload', array(&$this, 'callback'));
            } 
            return true; 
        } 
        return false; 
    } 
    function getName() { 
        return 'ClamAVPlugin'; 
    } 
    function getDisplayName() { 
        return 'ClamAV Plugin'; 
    } 
    function getDescription() { 
        return 'Scans uploaded articles for viruses before permitting submission'; 
    } 

    function callback($hookName, $args) { 
        $fileName  =& $args[0];
        $type      =& $args[1];
        $fileId    =& $args[2];
        $overwrite =& $args[3];
        $result    =& $args[4]; // Set to false and return true to indicate upload failure

        // Ask ClamAV for a verdict on $_FILES[$fileName]['tmp_name']
        ini_set('error_reporting',E_ALL);
        $clam = new Net_Clamd(CLAMDSOCKET);
        $clam_version = $clam->version();
        if(!$clam_version) {
            $hasVirus = true;
            $virusScanMsg = "ClamAV is not running, therefore cannot accept files for virus scanning";
//throw new Exception("ClamAV is not running");
        }
        else {
            if(CLAMDISLOCAL) {
                $virus = $clam->scan($_FILES[$fileName]['tmp_name']);
            }
            else {
                $data = file_get_contents($_FILES[$fileName]['tmp_name']);
                $virus = $clam->instream($data);
            }
            $hasVirus = ('OK'!=substr($virus, -2));
            $virus=substr(strstr($virus, ': '), 2);
            $virusScanMsg = 'ClamAV version '.$clam_version.' says: ';
            if(!$hasVirus)
                $virusScanMsg=$virusScanMsg.'No virus found';
            else
                $virusScanMsg=$virusScanMsg.$virus;
        }
//$hasVirus=true;
        $session =& Request::getSession();
        $session->setSessionVar('hasVirus', $hasVirus);
        $session->setSessionVar('virusScanMsg', $virusScanMsg);
//setcookie('hasVirus', $hasVirus);
//setcookie('virusScanMsg', $virusScanMsg);
        if($hasVirus) {
            $result = false;
            return true;
        }
        else {
            return false;
        }
    } 
} 
?>