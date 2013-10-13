<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 8/5/13
 * Time: 1:13 PM
 * 
 */

abstract class Application_Model_Import_Handler_ParserAbstract
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var array
     */
    protected $_tags;

    public function __construct()
    {
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->_file = $file;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->_tags = $tags;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->_tags;
    }




}