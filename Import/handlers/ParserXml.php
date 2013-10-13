<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 7/27/13
 * Time: 7:14 PM
 * 
 */

class Application_Model_Import_Handler_ParserXml
    extends Application_Model_Import_Handler_ParserAbstract
    implements Application_Model_Import_Handler_HandlerInteface
{
    /**
     * @var string
     */
    protected $_filePath;

    /**
     * @var XMLReader
     */
    protected $_readerXml;

    /**
     * @var array
     */
    protected $_tags;

    public function __construct(  )
    {
//        parent::__construct( $fileUrl, $tags );

        $this->setReaderXml( new XMLReader );

//        $this->_readerXml->open($this->_file, null, 1);

    }

    /**
     * сравниваем настоящий тег с тегами которые найти должны
     * @return null | array
     */
    protected function parseElement()
    {
        $resultData = null;
        $activeTag = $this->getReaderXml()->name;
//        сравниваем теги
        $resultSearch = array_search( $activeTag, $this->getTags());
//        если найден тег вытаскиваем данные и переходим к следующему пропуская вложености
        if( $resultSearch !== false ) {
            $resultData['tag'] = $activeTag;
            $resultData['data'] = $this->getReaderXml()->readOuterXml();
            $this->getReaderXml()->next();
        }

        return $resultData;

    }

    /**
     *
     * @return array|null
     */
    public function parse()
    {
        do
        {
            $elementData = $this->parseElement();
        }
        while( ($elementData === null) && $this->_readerXml->read() );

        return $elementData;

    }

    public function __destruct()
    {
        $this->_readerXml->close();
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->_filePath = $filePath;

        $this->getReaderXml()->open( $this->getFilePath(), null, 1 );
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->_filePath;
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

    /**
     * @param \XMLReader $readerXml
     */
    public function setReaderXml($readerXml)
    {
        $this->_readerXml = $readerXml;
    }

    /**
     * @return \XMLReader
     */
    public function getReaderXml()
    {
        return $this->_readerXml;
    }



}