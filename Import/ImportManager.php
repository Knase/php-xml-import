<?php
/**
 * Created by JetBrains PhpStorm.
 * User: knase
 * Date: 2/28/13
 * Time: 10:16 PM
 *
 */
class Application_Model_Import_ImportManager
{
    public static function createFileNameByCompanyId( $hashParam )
    {

        $fileDate = static::getNowTimeForFileFile();
        $hash = md5( $hashParam );
        $file = $fileDate . '_' . $hash . '.xml';
        return $file;
    }

    protected static function getNowTimeForFileFile()
    {
        $datetime = time();
        $fileDate = date( 'Y_m_d_Hi', $datetime );
        return $fileDate;
    }

    public static function validURL( $url  )
    {
        //return true;
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $result = false;
        if( (200 <= $httpCode) &&  ($httpCode < 400) ) {
            $result =true;
        }
        curl_close($handle);
        return $result;

    }

    /**
     * @param int $id
     */
    public static function createMpath( $id = 0 )
    {
        if($id) {
            $records = Application_Model_CompanyCatalogs_Peer
                ::getIndustries( array('company_id' => $id, 'status_entry' => array( 'active', 'inactive'), ));
            foreach( $records as $record ) {
                $parent = $record->getParentId();
                if( !empty($parent) ) {
                    $mpath = self::getMpath($records, $parent) . '.' . $parent . '.';
                    $record->setMpath($mpath);
                    $record->save();
                }
            }
        }
    }

    /**
     * @param array $records
     * @param       $idRecord
     *
     * @return string
     */
    protected static function getMpath( array $records, $idRecord )
    {
        if( isset($records[$idRecord]) ) {
            $parent = $records[$idRecord]->getParentId();
            if( !empty($parent) ) {
                unset($records[$idRecord]);
                return self::getMpath($records, $parent) . '.' . $parent;
            }
        }
        return;
    }



}
