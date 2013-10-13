<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/7/13
 * Time: 2:40 PM
 * 
 */

class Application_Model_Import_ParserElementFactory implements Application_Model_Import_ParserElementFactoryInterface
{
    public function createParserElement()
    {
        return new Application_Model_Import_Handler_ParserXmlElement();
    }

}