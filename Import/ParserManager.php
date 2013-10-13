<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 7/27/13
 * Time: 2:57 PM
 *
 */


class TypeDataEnum
{
    const XML = 'xml';
    const CSV = 'csv';

}

class TypeParse
{
    const INSERT = 'insert';
    const UPDATE = 'update';
}

class Application_Model_Import_ParserManager
{

    /**
     * @var Application_Model_Import_Handler_ParserAbstract
     */
    protected $_parser;

    /**
     * @var array
     */
    protected $_tagsArray;

    /**
     * @var string
     */
    protected $_file;

    /**
     * @var Application_Model_Import_Handler_DataHandler
     */
    protected $_dataHandler;

    /**
     * insert or update
     * @var string
     */
    protected $_typeParse;

    /**
     * xml or csv
     * @var string
     */
    protected $_typeData;

    /**
     * @var Application_Model_Import_Handler_FileHandler
     */
    protected $_fileHandler;

    /**
     * @var Application_Model_XmlConfigValues
     */
    protected $_modelTagsConfig;

    /**
     * @var Application_Model_Import_ParserElementFactoryInterface
     */
    protected $_factoryParseElementHandler;


    /**
     * @var Application_Model_ParsePriceLists
     */
    protected $_modelPriceList;


    /**
     * Конструктор
     * тут инициализируем handler данных и handler работы с файлами
     * создаем фабрику по созданию парсера элемента
     * можно вынести в отдельную функцию
     */
    public function __construct()
    {
        $this->setFileHandler( new Application_Model_Import_Handler_FileHandler() );

        $this->setDataHandler( new Application_Model_Import_Handler_DataHandler() );

        $this->setFactoryParseElementHandler( new Application_Model_Import_ParserElementFactory() );

    }


    /**
* Проводит парсинг по статусу
* @param string $statusImport
*
* @return bool
*/
    protected function _import(  $statusImport )
    {
//        задаем модель по статусу
        $this->setModelPriceList( $this->fetchRecordPriceListByStatus($statusImport) );
        if( $this->getModelPriceList() === null ) {
            return false;
        }
//        получаем запись конфига парсинга
        $this->setModelTagsConfig( Application_Model_PriceListTagsType_Peer
                ::getById( $this->getModelPriceList()->getConfigXmlId()) );

        $this->setTagsArray( $this->getModelTagsConfig()->getAllTagsArray() );

//        через handler файлов  скачиваем файл по ссылке
        $this->getFileHandler()->setCompanyId( $this->getModelPriceList()->getCompanyId() );
        $this->getFileHandler()->downloadFile( $this->getModelPriceList()->getPathUrl() );
        $this->getModelPriceList()->setFileName( $this->getFileHandler()->getFileName() );
        $this->getModelPriceList()->save();

//        через фабрику создаем парсер по обработки главных тегов и инициализируем значения в нем
        $parserFactory = new Application_Model_Import_ParserFactory();
        $this->setParser( $parserFactory->createParser( $this->getTypeData() ) );
        $this->getParser()->setFilePath( $this->getFileHandler()->getPath() );
        $this->getParser()->setTags( $this->getModelTagsConfig()->getGlobalTagConfig() );
//        начинаем парсинг
        $this->parseData();
        $this->_moveStatus('done_process');

        return true;
    }


    protected function _moveStatusInProcess()
    {
        $this->_modelPriceList->setStatus('in_process');
        return $this->_modelPriceList->save();
    }

    /**
* @param status $status
*
* @return mixed
*/
    protected function _moveStatus( $status )
    {
        $this->_modelPriceList->setStatus( strval($status) );
        return $this->_modelPriceList->save();
    }

    /**
     * @param string $status
     *
     * @return Application_Model_ParsePriceLists|null
     */
    public function fetchRecordPriceListByStatus( $status )
    {
        return Application_Model_ParsePriceLists_Peer::getRecordByStatus( strval($status) );
    }


    public function import()
    {
        $this->setTypeParse('insert');
        $insert = $this->_import('new');

        if( !$insert ) {
            $this->setTypeParse('update');
            $this->_import('ready_for_process');
        }



    }

    /**
*Функция парсинга поэлемента
*/
    public function parseData()
    {
// ну поехали
//        парсим поэлементно
        do {
//            парсим и получаем масив $data
//            $data['tag'] - тег который спарсили
//            $data['data'] - строка xml тега
            $data = $this->getParser()->parse();
            if( !empty($data) && isset($data['tag']) ) {
//                создаем парсер элемента
                $elementParse = $this->getFactoryParseElementHandler()->createParserElement();
//              задаем теги для распаршивания
                $elementParse->setTags( $this->getElementParserTagsByKey( $data['tag'] ));
//                парсим данные по зеданым раньше тегам
                $dataImport = $elementParse->parseElement( $data['data'] );
//                через обработчик данных закидываем данные
                $this->getDataHandler()->insertData( $dataImport );

            }

        } while( $data !== null );

    }

    protected function getElementParserTagsByKey( $keyTag )
    {
        return array();
    }

    /**
     * @param array $tagsArray
     */
    public function setTagsArray($tagsArray)
    {
        $this->_tagsArray = $tagsArray;
    }



    /**
     * @param \Application_Model_ParsePriceLists $modelPriceList
     */
    public function setModelPriceList($modelPriceList)
    {
        $this->_modelPriceList = $modelPriceList;
    }

    /**
     * @return \Application_Model_ParsePriceLists
     */
    public function getModelPriceList()
    {
        return $this->_modelPriceList;
    }



    public function setDataHandler($dataHandler)
    {
        $this->_dataHandler = $dataHandler;
    }

    public function getDataHandler()
    {
        return $this->_dataHandler;
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
     * @param \Application_Model_Import_ParserFactoryInterface $parser
     */
    public function setParser( Application_Model_Import_ParserFactoryInterface $parserFactory )
    {
        $this->_parser = $parserFactory->createParser();
    }

    /**
     * @return \Application_Model_Import_ParserXml
     */
    public function getParser()
    {
        return $this->_parser;
    }

    public function setTypeData($typeData)
    {
        $this->_typeData = $typeData;
    }

    public function getTypeData()
    {
        return $this->_typeData;
    }

    public function setTypeParse($typeParse)
    {
        $this->_typeParse = $typeParse;
    }

    public function getTypeParse()
    {
        return $this->_typeParse;
    }

    /**
     * @param \Application_Model_Import_Handler_FileHandler $fileHandler
     */
    public function setFileHandler($fileHandler)
    {
        $this->_fileHandler = $fileHandler;
    }

    /**
     * @return \Application_Model_Import_Handler_FileHandler
     */
    public function getFileHandler()
    {
        return $this->_fileHandler;
    }

    /**
     * @param \Application_Model_PriceListTagsType $modelTags
     */
    public function setModelTagsConfig($modelTags)
    {
        $this->_modelTags = $modelTags;
    }

    /**
     * @return \Application_Model_PriceListTagsType
     */
    public function getModelTagsConfig()
    {
        return $this->_modelTags;
    }

        /**
     * @param \Application_Model_Import_ParserElementFactoryInterface $factoryParseElementHandler
     */
    public function setFactoryParseElementHandler($factoryParseElementHandler)
    {
        $this->_factoryParseElementHandler = $factoryParseElementHandler;
    }/**
     * @return \Application_Model_Import_ParserElementFactoryInterface
     */
    public function getFactoryParseElementHandler()
    {
        return $this->_factoryParseElementHandler;
    }






}