<?php
namespace Amazon\Service;

use Exception;
use GeoIp2\Database\Reader;
use Amazon\DB\Db;

class AmazonUrlBuilder
{
//    const KINDLE_BOOK_URI = '/dp/__ID__/';

    public function __construct()    
    {
        $geo = new Reader(__DIR__ . '/../data/GeoLite2-Country.mmdb');
        $this->geo = $geo;
        $this->db = new \SQLite3(__DIR__ . "/../amazonRedirect.db");
        $this->urlPrefix = $this->db->querySingle("SELECT value FROM config WHERE key = 'urlPrefix'");
        $this->defaultStoreURL = $this->db->querySingle("SELECT value FROM config WHERE key = 'defaultStoreURL'");
        $this->KindleURI = $this->db->querySingle("SELECT value FROM config WHERE key = 'KindleURI'");
        $this->fallbackASIN = $this->db->querySingle("SELECT value FROM config WHERE key = 'fallbackASIN'");
    }

    public function getLocalKindleUrl($ip)
    {
        // read the geoIP database
    $reader = new Reader(__DIR__ . '/../data/GeoLite2-Country.mmdb');

    $record = $reader->country($ip);
    try {
        $iso = $record->country->isoCode;
        $selectedIso = $iso;
            $geoStoreUrl = $this->db->querySingle("SELECT store_url FROM stores WHERE ISO = '$iso'");
            // if it's not in the database, default
            $storeUrl = $geoStoreUrl ? $geoStoreUrl : $this->defaultStoreURL;
    } catch (Exception $ex) {
        // do something maybe
    }
    $amazonId = $this->getASINFromURL();

    $url = 'http://' . $storeUrl . $this->KindleURI;
    $url = str_replace('__TEMP_URI__', $amazonId, $url);
    $url = $this->appendAssociateId($url, $selectedIso);

    // Update the stats
    $this->updateStats($amazonId,$ip);
    return $url;
    }

    public function updateStats($amazonId,$ip)
    {
        $this->db->exec("INSERT INTO stats (IP,ASIN) VALUES ('$ip','$amazonId')");
    }

    public function appendAssociateId($url, $iso)
    {
        // I am assuming I don't need to prepare this since it isn't being run by the public i.e. you should trust yourself.
        $affiliateCode = $this->db->querySingle("SELECT affiliate_code FROM stores WHERE ISO = '$iso'");

        // get value
        if (!$affiliateCode) {
            return $url;
        }

        if (stripos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }

        $url .= 'tag=' . $affiliateCode;

        return $url;
    }

    public function getASINFromURL() {
        $url = parse_url($_SERVER['REQUEST_URI']);

        $request = filter_var($url["path"],FILTER_SANITIZE_URL);

        //now remove amazon-url or subdir thing if exists
        if (!empty($this->urlPrefix) ) {
            $path = substr($request,strlen($this->urlPrefix));
        }

        //find the path in the database (need error check here as well)
        $asin = $this->db->querySingle("SELECT ASIN FROM url_mapping WHERE url like '%$path'");
        if ($asin === NULL ) {
          $asin = $this->fallbackASIN;
        }

        //default action?
        return $asin;
    }
}
