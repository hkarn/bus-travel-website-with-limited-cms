<?php

namespace HisingeBussAB\RekoResor\website;
use HisingeBussAB\RekoResor\website\includes\classes\DB;
use HisingeBussAB\RekoResor\website\includes\classes\DBError;
use HisingeBussAB\RekoResor\website as root;
use HisingeBussAB\RekoResor\website\includes\classes\Functions as functions;


class RenderProdCatalog {

  public static function render() {

    header('Content-type: text/csv; charset="utf-8"',true);

    try {
      $pdo = DB::get();

      $sql = "SELECT resor.id, resor.seo_description, resor.namn, resor.url, resor.bildkatalog, resor.pris, resor.cat_addr_street, resor.cat_addr_city,
                     resor.cat_addr_region, resor.cat_addr_country, resor.cat_addr_zip, resor.cat_lat, resor.cat_long, resor.cat_neighborhood, resor.cat_type,
                     datum.datum AS datum FROM " . TABLE_PREFIX . "resor AS resor
              LEFT OUTER JOIN " . TABLE_PREFIX . "datum AS datum ON resor.id = datum.resa_id
              LEFT OUTER JOIN " . TABLE_PREFIX . "kategorier_resor AS k_r ON resor.id = k_r.resa_id
              LEFT OUTER JOIN " . TABLE_PREFIX . "kategorier AS kategorier ON kategorier.id = k_r.kategorier_id
              WHERE kategorier.kategori != 'gruppresor' AND resor.aktiv = 1 AND datum > (NOW() - INTERVAL 1 DAY)
              GROUP BY datum
              ORDER BY datum;";



        $sth = $pdo->prepare($sql);
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        //var_dump($result);
      } catch(\PDOException $e) {
        DBError::showError($e, __CLASS__, $sql);
        $errorType = "Databasfel";
        throw new \RuntimeException("Databasfel vid laddning av resor.");
      }



      header('Content-type: text/xml; charset="utf-8"',true);
      echo '<?xml version="1.0" encoding="UTF-8"?>
            <rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
              <channel>
                <title>Rekå Resor Tours</title>
                <link rel="self" href="http' . APPEND_SSL . '://' . DOMAIN . '/feed/get-products.xml"/>
                <description>Rekå Resor Tour Catalog as products</description>
                ';
                $lastitemid = false;
                $repeatitem = 0;
                foreach($result as $item) {
                  if (!empty($item['cat_addr_city']) && !empty($item['cat_addr_region'])) {
                    $server_path = __DIR__ . '/upload/resor/' . $item['bildkatalog'] . '/';
                    $web_path = "http" . APPEND_SSL . "://" . $_SERVER['SERVER_NAME'] . "/upload/resor/" . rawurlencode($item['bildkatalog']) . "/";
                    $imgfiles = functions::get_img_files($server_path);
                    if (empty($imgfiles)) {
                      $imgfiles[0]['file'] = "1_generic.jpg";
                      $imgfiles[0]['thumb'] = "small_1_generic.jpg";
                      $web_path = "http" . APPEND_SSL . "://" . $_SERVER['SERVER_NAME'] . "/upload/resor/generic/";
                    }
                    $neighborhoods = explode(",", $item['cat_neighborhood']);
                    $categories = explode(",", $item['cat_type']);
                    $cat_lat = str_replace(",",".",$item['cat_lat']);
                    $cat_long = str_replace(",",".",$item['cat_long']);
                    echo '<item>';
                    if ($lastitemid == $item['id']) {
                      $repeatitem++;
                      echo '<g:item_group_id>' . htmlentities(trim($item['id']), ENT_XML1) . '</g:item_group_id>';
                      echo '<g:id>' . htmlentities(trim($item['id'] . '0' . $repeatitem), ENT_XML1) . '</g:id>';
                    } else {
                      echo '<g:item_group_id>' . htmlentities(trim($item['id']), ENT_XML1) . '</g:item_group_id>';
                      echo '<g:id>' . htmlentities(trim($item['id']), ENT_XML1) . '</g:id>';
                      $repeatitem = 0;
                    }
                    $lastitemid = $item['id'];
                    echo '<g:availability>in stock</g:availability>
                    <g:condition>new</g:condition>
                    <g:brand>Rekå Resor</g:brand>
                    <g:title>' . htmlentities(trim($item['namn']), ENT_XML1) . '</g:title>
                    <g:description>' . htmlentities(trim($item['seo_description']), ENT_XML1) . '</g:description>
                    ';
                    $i=0;
                    $additional_image_link = '';
                    foreach($imgfiles as $imgfile) {
                      if ($i == 0) {
                        echo '<g:image_link>' . $web_path . rawurlencode(trim($imgfile['file'])) . '</g:image_link>
                        ';
                      } else {
                        $additional_image_link .= $web_path . rawurlencode(trim($imgfile['file'])) . ' ,';
                      }
                      $i++;
                    }
                    if (!empty($additional_image_link)) {
                      echo '<g:additional_image_link>' . $additional_image_link . '</g:additional_image_link>
                      ';
                    }
                    echo '<g:price>' . htmlentities(trim($item['pris']), ENT_XML1) . ' SEK</g:price>
                    <g:google_product_category>Arts &amp; Entertainment &gt; Event Tickets</g:google_product_category>

                    <g:link>http' . APPEND_SSL . '://' . DOMAIN . '/resa/' . rawurlencode(trim($item['url'])) . '</g:link>
                    </item>
                    ';
                  }
                }
              echo '
              </channel>
              </rss>
              ';


    }
}
