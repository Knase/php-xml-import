<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/5/13
 * Time: 3:24 PM
 * 
 */

class Application_Model_Import_ParserFactory implements Application_Model_Import_ParserFactoryInterface
{

    public function createParser( $type )
    {
        return new Application_Model_Import_Handler_ParserXml();
    }
}