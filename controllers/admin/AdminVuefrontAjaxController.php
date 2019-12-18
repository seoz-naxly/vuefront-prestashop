<?php
/**
 * 2019 (c) VueFront
 *
 * MODULE VueFront
 *
 * @author    VueFront
 * @copyright Copyright (c) permanent, VueFront
 * @license   MIT
 * @version   0.1.0
 */

class AdminVuefrontAjaxController extends ModuleAdminController
{

  public function ajaxProcessVfTurnOff()
    {
        if (strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false) {
            if (file_exists(_PS_ROOT_DIR_ . '/modules/vuefront/.htaccess.txt')) {
                $content = file_get_contents(_PS_ROOT_DIR_ . '/modules/vuefront/.htaccess.txt');
                file_put_contents(_PS_ROOT_DIR_ . '/.htaccess', $content);
                unlink(_PS_ROOT_DIR_ . '/modules/vuefront/.htaccess.txt');
            }
        }

        $this->ajaxProcessVfInformation();
    }

  public function ajaxProcessVfTurnOn() {
    $catalog = Tools::getHttpHost(true). __PS_BASE_URI__;
    try {
        $catalog_url_info = parse_url($catalog);

        $catalog_path = $catalog_url_info['path'];

        if (strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false) {

            if(!file_exists(_PS_ROOT_DIR_ . '/.htaccess')) {
                file_put_contents(_PS_ROOT_DIR_.'/.htaccess', "Options +FollowSymlinks
Options -Indexes
<FilesMatch \"(?i)((\.tpl|\.ini|\.log|(?<!robots)\.txt))\">
Require all denied
</FilesMatch>
RewriteEngine On
RewriteBase ".$catalog_path."
RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]
RewriteRule ^googlebase.xml$ index.php?route=extension/feed/google_base [L]
RewriteRule ^system/download/(.*) index.php?route=error/not_found [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*\.(ico|gif|jpg|jpeg|png|js|css)
RewriteRule ^([^?]*) index.php?_route_=$1 [L,QSA]");
            }

            if (file_exists(_PS_ROOT_DIR_ . '/.htaccess')) {
                $inserting = "# VueFront scripts, styles and images
RewriteCond %{REQUEST_URI} .*(_nuxt)
RewriteCond %{REQUEST_URI} !.*/vuefront/_nuxt
RewriteRule ^([^?]*) vuefront/$1

# VueFront pages

# VueFront home page
RewriteCond %{REQUEST_URI} !.*(images|index.php|.html|admin|.js|.css|.png|.jpeg|.ico|wp-json|wp-admin|checkout)
RewriteCond %{QUERY_STRING} !.*(rest_route)
RewriteCond %{DOCUMENT_ROOT}".$catalog_path."vuefront/index.html -f
RewriteRule ^$ vuefront/index.html [L]

RewriteCond %{REQUEST_URI} !.*(images|index.php|.html|admin|.js|.css|.png|.jpeg|.ico|wp-json|wp-admin|checkout)
RewriteCond %{QUERY_STRING} !.*(rest_route)
RewriteCond %{DOCUMENT_ROOT}".$catalog_path."vuefront/index.html !-f
RewriteRule ^$ vuefront/200.html [L]

# VueFront page if exists html file
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*(images|index.php|.html|admin|.js|.css|.png|.jpeg|.ico|wp-json|wp-admin|checkout)
RewriteCond %{QUERY_STRING} !.*(rest_route)
RewriteCond %{DOCUMENT_ROOT}".$catalog_path."vuefront/$1.html -f
RewriteRule ^([^?]*) vuefront/$1.html [L,QSA]

# VueFront page if not exists html file
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*(images|index.php|.html|admin|.js|.css|.png|.jpeg|.ico|wp-json|wp-admin|checkout)
RewriteCond %{QUERY_STRING} !.*(rest_route)
RewriteCond %{DOCUMENT_ROOT}".$catalog_path."vuefront/$1.html !-f
RewriteRule ^([^?]*) vuefront/200.html [L,QSA]";

                $content = file_get_contents(_PS_ROOT_DIR_ . '/.htaccess');

                file_put_contents(_PS_ROOT_DIR_ . '/modules/vuefront/.htaccess.txt', $content);

                preg_match('/# VueFront pages/m', $content, $matches);

                if (count($matches) == 0) {
                    $content = preg_replace_callback('/#Domain:\s.*$/m', function ($matches) use ($inserting) {
                        return $matches[0] . PHP_EOL . $inserting . PHP_EOL;
                    }, $content);

                    file_put_contents(_PS_ROOT_DIR_ . '/.htaccess', $content);
                }
            }
        }
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

    $this->ajaxProcessVfInformation();
  }

  public function ajaxProcessVfUpdate() {
    try {
      $tmpFile = tempnam(sys_get_temp_dir(), 'TMP_');
      rename($tmpFile, $tmpFile .= '.tar');
      file_put_contents($tmpFile, file_get_contents($_POST['url']));
      $this->removeDir(_PS_ROOT_DIR_ . '/vuefront');
      $phar = new PharData($tmpFile);
      $phar->extractTo(_PS_ROOT_DIR_ . '/vuefront');
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

    $this->ajaxProcessVfInformation();
  }

  private function removeDir($dir)
  {
      if (is_dir($dir)) {
          $objects = scandir($dir);
          foreach ($objects as $object) {
              if ($object != "." && $object != "..") {
                  if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object)) {
                      $this->removeDir($dir . "/" . $object);
                  } else {
                      unlink($dir . "/" . $object);
                  }
              }
          }
          rmdir($dir);
      }
  }

  public function ajaxProcessVfInformation() {
      $extensions = [];

      $moduleInstance = Module::getInstanceByName('vuefront');

      if (Module::isInstalled('prestablog')) {
          $blogInstance = Module::getInstanceByName('prestablog');
          $extensions[] = [
              'name' => Module::getModuleName('prestablog'),
              'version' => $blogInstance->version,
              'status' => Module::isInstalled('prestablog')
          ];
      } else {
          $extensions[] = [
              'name' => Module::getModuleName('prestablog'),
              'version' => '',
              'status' => Module::isInstalled('prestablog')
          ];
      }

      $is_apache = strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false;
      $status = false;
      if(file_exists(_PS_ROOT_DIR_.'/modules/vuefront/.htaccess.txt')) {
          $status = true;
      }
      die(
        Tools::jsonEncode(
          [
            'apache' => $is_apache,
            'backup' => 'modules/vuefront/.htaccess.txt',
            'htaccess' => file_exists(_PS_ROOT_DIR_ . '/.htaccess'),
            'status' => $status,
            'phpversion' => phpversion(),
            'plugin_version' => $moduleInstance->version,
            'extensions' => $extensions,
            'cmsConnect' => Tools::getHttpHost(true).
            __PS_BASE_URI__.'index.php?controller=graphql&module=vuefront&fc=module',
            'server' => $_SERVER["SERVER_SOFTWARE"]
        ]
        )
      );
  }

  public function ajaxProcessProxy()
  {
    $body = Tools::file_get_contents('php://input');;
    if (!function_exists('getallheaders')) {
      function getallheaders()
      {
          $headers = [];
          foreach ($_SERVER as $name => $value) {
              if (substr($name, 0, 5) == 'HTTP_') {
                  $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
              }
          }
          return $headers;
      }
    }
    $headers = getallheaders();

    $cHeaders = array('Content-Type: application/json');

    if(!empty($headers['token'])) {
        $cHeaders[] = 'token: '.$headers['token'];
    }
    if(!empty($headers['Token'])) {
        $cHeaders[] = 'token: '.$headers['Token'];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.vuefront.com/graphql');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cHeaders);
    $result = curl_exec($ch);
    curl_close($ch);
    die($result);
  }
}
