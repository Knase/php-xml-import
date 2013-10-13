<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/6/13
 * Time: 5:24 PM
 * 
 */

class Application_Model_Import_Handler_FileHandler implements Application_Model_Import_Handler_HandlerInteface
{
//    @todo Дописать класс
    protected $_companyId;

    protected $_path;

    protected $_fileName;

    /**
     * качаем файл по адресу
     * @param string $url
     * @param int $companyId
     *
     * @return bool|string
     */
    protected function getFilePathDownload( $url, $companyId )
    {
        $result = false;

//        if( $result ) {
        $fileName = Application_Model_Import_ImportManager::createFileNameByCompanyId( $companyId );
        $path = APPLICATION_PATH.'/../public/uploads/xml' ;
        $path .= '/' . $companyId;
        if (!is_dir($path)) {
            mkdir($path, 0777);
        }
        $path .=  '/' . $fileName ;

        $this->downloadFile($url, $path);
        $result = $fileName;
//        }
        return $result;
    }

    public function setCompanyId($companyId)
    {
        $this->_companyId = $companyId;
    }

    public function getCompanyId()
    {
        return $this->_companyId;
    }

    public function setPath( $path )
    {
        $this->_path = APPLICATION_PATH . $path;
    }

    public function createDir()
    {

    }

    public function addToPath( $dir )
    {

    }

    public function getPath()
    {
        return $this->_path;
    }

    public function createFileName()
    {
        $this->_fileName = Application_Model_Import_ImportManager::createFileNameByCompanyId( $this->getCompanyId() );
    }

    public function getFileName()
    {
        return $this->_fileName;
    }

    public function downloadFile ($url) {

//

    }

    public function createFileNameByCompanyId( $hashParam )
    {

        $fileDate = static::getNowTimeForFileFile();
        $hash = md5( $hashParam );
        $file = $fileDate . '_' . $hash . '.xml';
        return $file;
    }

    protected  function getNowTimeForFileFile()
    {
        $datetime = time();
        $fileDate = date( 'Y_m_d_Hi', $datetime );
        return $fileDate;
    }

}