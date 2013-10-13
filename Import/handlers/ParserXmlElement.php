<?php
/**
 * Класс для разборки xml в масив главных тегов
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/1/13
 * Time: 5:00 PM
 * 
 */

class Application_Model_Import_Handler_ParserXmlElement
    extends  Application_Model_Import_Handler_ParserElement
    implements Application_Model_Import_Handler_HandlerInteface
{


    protected $_tags;

    public function __construct()
    {

    }

    public function setTags( array $tags )
    {
        $this->_tags = $tags;
    }
    public function setSimpleXml($simpleXml)
    {
        $this->_simpleXml = $simpleXml;
    }

    public function getSimpleXml()
    {
        return $this->_simpleXml;
    }

    public function parseElement( $data )
    {

    }

}