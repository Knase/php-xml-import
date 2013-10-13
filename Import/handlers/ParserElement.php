<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/5/13
 * Time: 1:41 PM
 * 
 */

class Application_Model_Import_Handler_ParserElement
    extends Application_Model_Import_Handler_ParserElementAbstract
    implements Application_Model_Import_Handler_HandlerInteface
{

    /**
     * @var array
     */
    protected $_tagsParse;



    public function parseElement( $data )
    {

    }

    /**
     * @param array $tagsParse
     */
    public function setTagsParse($tagsParse)
    {
        $this->_tagsParse = $tagsParse;
    }

    /**
     * @return array
     */
    public function getTagsParse()
    {
        return $this->_tagsParse;
    }



}