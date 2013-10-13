<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 2/18/13
 * Time: 3:56 PM
 *
 */
class Application_Service_ParserXml extends Application_Service_Abstract
{
    protected $_url;
    protected $_file;
    protected $_data;
    protected $_parser = null;
    protected $_array = array();
    protected $_tag;
    protected $_count = 0;
    protected $_countCategory = 0;
    protected $_companyId;
    protected $_update = true;
    protected $_isParseNow = false;
    protected $_isShortParse = false;
    protected $_currencies = array();
    protected $_offerTagName = '';
    protected $_offerTags = array();
    //Генко. Тут эта переменная, в нее я кладу в import_service теги с новой формы для каренси и каталога
    protected $_tagsCurrencyCatalog=array();

    /**
     * Ну вы понели?
     * @param $tags
     * @return void
     */
    public function setTagsCurrencyCatalog($tags)
    {
        $this->_tagsCurrencyCatalog=$tags;
    }

    public function __construct( $url, $companyId, $offerName = '', $tagNames = null )
    {
        if ( !empty($url)
            && ( mb_detect_encoding( $this->_url ) == 'ASCII'
                || mb_detect_encoding( $this->_url ) == 'UTF-8' ) ) {
                    $this->_url = $url;
        }

        if( !empty($companyId) ) {
            $this->_companyId = intval( $companyId );
        }

        if( !empty($offerName) && !empty($tagNames) ) {
            $this->_offerTagName = strval( $offerName );
            $this->_offerTags = $tagNames;
        }
    }

    /**
     * init model object for table company_catalogs or company_products
     * @return $this
     */
    protected function _initDataClass()
    {
        if( $this->_isShortParse ) {
            $tableName = Application_Model_CompanyCatalogs_Peer::getTableName();
        } else {
            $tableName = Application_Model_CompanyProducts_Peer::getTableName();
        }
        $this->_data = new Application_Model_DbTable_ModelArray( $tableName, $this->_companyId, 100 );
        return $this;
    }

    /**
     * @param bool $update
     * @param bool $parseCategories
     *
     * @return int
     */
    public function run( $update = true, $parseCategories = true  )
    {
        $this->_isShortParse = $parseCategories;
        $this->_initDataClass();
        $this->_update = $update;
        $timeStart = time();
        $memoryStart = memory_get_usage( );
        //print_r($this->_currencies);exit;
        $res = $this->_startParse();
        //тут где то на этом шаге выше заполняется this->_currencies
        //print_r($this->_currencies);exit;
        if( $res === false ) {
            return false;
        }

        if( !$update  ) {
                if($parseCategories) {
                    $this->_setCurrencyInSql();
                    $this->_setHasCategories();
//                    $this->_data->setRowsInArrayRows( $this->_array );
                    $this->_data->insertInSql();

                } else{
                    $this->_data->insertProducts();
                }
        } elseif ($update){
//
            if(!$this->_isShortParse) {
                $this->_data->updateProducts();
            }
        }

        $memoryEnd = memory_get_usage( );
        $timeEnd = time();

        echo "Time: \t" . ($timeEnd-$timeStart), "\n";
        echo "Memory: \t" . ($memoryEnd - $memoryStart), "\n";

        return true;
    }

    protected function _setHasCategories()
    {

        $priceListModel = Application_Model_ParsePriceLists_Peer::getByCompanyId( $this->_companyId );
        $catalog = Application_Model_CompanyCatalogs_Peer::fetchParsedCatalogByCompanyId( $this->_companyId, true );
        $hash = md5(Zend_Json::encode( $catalog ));
        $listHash = $priceListModel->getCatalogHash();
        if( strcasecmp($hash, $listHash) !== 0  ) {
            $priceListModel->setCatalogHash( $hash );
            $priceListModel->save();
        }

    }

    /**
     * @return bool
     */
    protected function _startParse()
    {
        $this->_parser = xml_parser_create();
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, "startElement",
            "endElement");
        xml_set_character_data_handler($this->_parser,'getElement');
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);

        $this->_file = fopen( $this->_url, 'rb');
        if( $this->_file === false ) {
            return false;
        }
        $clasterSize = 8192;
        $this->_isParseNow = true;
        while ( ($this->_isParseNow == true) && ($data = fread($this->_file, $clasterSize)) ) {
            if (!xml_parse($this->_parser, $data, feof($this->_file))) {
                die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->_parser)),
                    xml_get_current_line_number($this->_parser)));

            }

        }
        xml_parser_free($this->_parser);
        fclose( $this->_file);
        return true;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function validURL( $url = '' )
    {
        if( empty($url) ) {
            $url = $this->_url;
        }
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $result = false;
        if( (200 <= $httpCode) &&  ($httpCode < 300) ) {
            $result =true;
        }
        curl_close($handle);
        return $result;

    }

    public function getElement($parser,  $data )
    {

            if( $this->_isShortParse && $this->_tag == $this->_tagsCurrencyCatalog['category']) {
                $this->_parseCatalogName( $data );    //похоже тут дергается, если встретили тег category/ хардкод
            } elseif( !$this->_isShortParse ) {
                $this->_setField( $data );
            }


    }

    public function startElement($parser, $tag, $attrs)
    {
        /*GENKO*/
        if( $this->_isShortParse ) {
            if( strcasecmp($tag, $this->_tagsCurrencyCatalog['currency'] /*'currency'*/) == 0 ) {
                $this->_parseCurrencyAtrib( $attrs );
            } elseif( strcasecmp($tag, $this->_tagsCurrencyCatalog['category'] /*'category'*/) == 0 ) {
                $this->_beginParseCatalog( $attrs );
            }
        }  // print_r($this->_offerTagName);
        if( $tag == $this->_offerTagName ) {

            if ($this->_isShortParse && $this->_update ) {
//            if( $this->_isShortParse && $this->_update ) {

                $this->_data->addRowInArray( $this->_array, false);

                $this->_data->updateInSql();

                $this->_setCurrencyInSql();

                unset($this->_array);

                $this->_array = array();

                $this->_isShortParse = false;

                $this->_initDataClass();
            }
            $this->_analiceOfferAttribs( $attrs );
// проверить на длинный парсинг анализ атрибутов
        }
        $this->_tag = $tag;

    }
    /**
     * Генрих: Я так понял в этой функции проверяется, если что то с currency поменялось, надо переписать ячейку currency
     * в таблице ParsePriceLists
     * @return void
     */
    protected function _setCurrencyInSql()
    {
        $priceListModel = Application_Model_ParsePriceLists_Peer::getByCompanyId( $this->_companyId );
        $currencies = Zend_Json::encode( $this->_currencies );
        if( !empty($currencies) ) {
            $currenciesOld = $priceListModel->getCurrency();
            if( strcasecmp($currencies, $currenciesOld) !== 0 ) {
                $priceListModel->setCurrency($currencies);
                $priceListModel->save();
            }
        }
    }

    protected function _analiceOfferAttribs( $attribs )
    {
        if( array_key_exists('attributs', $this->_offerTags) ) {
            foreach( $this->_offerTags['attributs'] as $key => $value ) {
                if( array_key_exists( $key, $attribs ) ) {
                    $name = $this->_offerTags['attributs'][$key];

                    if( !isset($this->_array[$name]) ) {
                        $this->_array[$name] = '';
                    }
                    $this->_array[$name] .= $attribs[$key];


                }
            }
        }

    }

    protected function _setField( $data )
    {
        $value = trim( $data );
        if( array_key_exists($this->_tag, $this->_offerTags) && !empty($value) ) {
            $key = $this->_tag;
            if( is_array($this->_offerTags[$key]) ) {
                foreach($this->_offerTags[$key] as $tagName ) {
                    $name = $tagName;

                    if( !isset($this->_array[$name]) ) {
                        $this->_array[$name] = '';
                    } else {
                        $this->_array[$name] .= ' ';
                    }
                    $this->_array[$name] .= $value;
                }
            } else {
                $name = $this->_offerTags[$key];

                if( !isset($this->_array[$name]) ) {
                    $this->_array[$name] = '';
                } else {
                    $this->_array[$name] .= ' ';
                }
                $this->_array[$name] .= $value;
            }

        }
    }

    protected function _beginParseCatalog( $attribs )
    {
         //print_r($attribs);exit;
        foreach( $attribs as $key => $value) {
            if( strcasecmp($key, $this->_tagsCurrencyCatalog['category_id']) == 0 ) {
                $this->_array['xml_id'] = intval( $value );
            }
            elseif( strcasecmp($key, $this->_tagsCurrencyCatalog['category_parent']) == 0 ) {
                $this->_array['xml_parent_id'] = intval( $value );
            }
        }

    }

    protected function _parseCatalogName( $name )
    {
        $value =  trim( $name );
        if( !empty($value)) {
            $value = htmlspecialchars( $value );
            $this->_array['status_entry'] = 'active';
            $this->_array['catalog_title'] = $value;
            $this->_array['company_id'] = $this->_companyId;
            $this->_array['translit_name'] = Application_Model_Catalog_CatalogManager::returnInTranslit( $value );
        }

    }

    protected function _endParseTag()
    {

        if( !$this->_isShortParse ) {
            $this->_array['company_id'] = $this->_companyId;
            $this->_array['xml_status'] = 'no_parse_image';
            $this->_array['status_entry'] = 'active';
            $hash = md5(serialize( $this->_array ));
            $this->_array['hash'] = $hash;
            if( $this->_update ) {
                $this->_data->addRowProductForUpdate( $this->_array );
            } else {
                $this->_data->addRowProductInArray( $this->_array );
            }
            unset($this->_array);
            $this->_array = array();
        }
    }

    protected function _endParseTagCatalog()
    {
        $this->_data->addRowInArray( $this->_array );
        unset($this->_array);
        $this->_array = array();
    }


    public function endElement($parser, $tag)
    {

            if( $this->_isShortParse ) {
                if( ($tag == $this->_tagsCurrencyCatalog['category'] /*&& $this->_tag == $this->_tagsCurrencyCatalog['category']*/) ) {
                    $this->_endParseTagCatalog();
                } elseif($this->_tag == $this->_tagsCurrencyCatalog['category'] && $tag != $this->_tagsCurrencyCatalog['category'] ) {

                    $this->_isParseNow = false;

                }
            } else {
                if($tag == $this->_offerTagName  ) {
                    $this->_endParseTag();
                }
            }

//        }

    }


    protected  function _parseCurrencyAtrib( $attrs )
    {     // exit;
//        if(isset($attrs['id'])){
//            $attrs['code']=$attrs['id'];
//        }

            if( array_key_exists($this->_tagsCurrencyCatalog['currency_id'] /*'code'*/, $attrs)) {
                $this->_currencies[$attrs[$this->_tagsCurrencyCatalog['currency_id'] ]]['code'] = $attrs[$this->_tagsCurrencyCatalog['currency_id'] ];
            }

            if( array_key_exists($this->_tagsCurrencyCatalog['currency_rate']/*'rate'*/, $attrs)) {
                $this->_currencies[$attrs[$this->_tagsCurrencyCatalog['currency_id']]]['rate'] = $attrs[$this->_tagsCurrencyCatalog['currency_rate']];
            }
        if( array_key_exists('main', $attrs)) {
                $this->_currencies[$attrs[$this->_tagsCurrencyCatalog['currency_id']]]['main'] = $attrs['main'];
            } //print_r($this->_currencies);exit;

    }

    public function getUrl()
    {
        if( isset($this->_url ) ) {
            return $this->_url;
        } else {
            return;
        }
    }

    public function setUrl( $url )
    {
        $this->_url = $url;
        return $this;
    }

    public function getCompanyId()
    {
        return $this->_companyId;
    }
    public function setCompanyId( $value )
    {
        $this->_companyId = intval( $value );
        return $this;
    }

    public function setOfferTagName( $value )
    {
        $this->_offerTagName = trim( $value );
        return $this;
    }

    public function getOfferTagName()
    {
        return $this->_offerTagName;
    }


    public function getOfferTags()
    {
        return $this->_offerTags;
    }
    public function setOfferTags( $value = array() )
    {
        $this->_offerTags =  $value ;
        return $this;
    }

    public function __destruct()
    {

    }
}
