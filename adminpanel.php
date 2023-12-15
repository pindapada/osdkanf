<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2 & 7.3
 * @ Decoder version: 1.0.6
 * @ Release: 10/08/2022
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
define("THEME_MODULE_NAME", "adminpanel");
define("ThemeModuleName", "croster");
global $s_s_pages;
$s_s_pages = [];
$s_s_pages[] = ["name" => ["clientarea", "Client Area"], "value" => ["priority" => "0.1", "frequency" => "never"]];
$s_s_pages[] = ["name" => ["submitticket", "Submit Ticket"], "value" => ["priority" => "0.1", "frequency" => "never"]];
$s_s_pages[] = ["name" => ["supporttickets", "Support Tickets"], "value" => ["priority" => "0.1", "frequency" => "never"]];
$s_s_pages[] = ["name" => ["affiliates", "Affiliates"], "value" => ["priority" => "0.1", "frequency" => "never"]];
$s_s_pages[] = ["name" => ["contact", "Pre Sales Contact Us"], "value" => ["add" => "1", "priority" => "0.4", "frequency" => "never"]];
$s_s_pages[] = ["name" => ["serverstatus", "Server Status"], "value" => ["add" => "1", "priority" => "0.8", "frequency" => "always"]];
$s_s_pages[] = ["name" => ["networkissues", "Network Issues"], "value" => ["add" => "1", "priority" => "0.3", "frequency" => "monthly"]];
$s_s_pages[] = ["name" => ["order", "Order"], "value" => ["add" => "1", "priority" => "0.3", "frequency" => "yearly"]];
$s_s_pages[] = ["name" => ["announcements", "Announcements"], "value" => ["add" => "1", "priority" => "0.6", "frequency" => "monthly"]];
$s_s_pages[] = ["name" => ["knowledgebase", "Knowledgebase"], "value" => ["add" => "1", "priority" => "0.4", "frequency" => "yearly"]];
$s_s_pages[] = ["name" => ["knowledgebasecat", "Knowledgebase Categories"], "value" => ["add" => "1", "priority" => "0.3", "frequency" => "yearly"]];
if (Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_pages")) {
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("page_type", "pages")->get();
    foreach ($items as $item) {
        if ($item->page_url != "index.php") {
            $custompagename = str_replace(".php", "", $item->page_url);
            $s_s_pages[] = ["name" => [$custompagename, $custompagename], "value" => ["add" => "1", "priority" => "0.4", "frequency" => "yearly"]];
        }
    }
}
if (!function_exists("ws_license_adminpanel")) {
    function ws_license_adminpanel($licensing_secret_key, $modulename, $ladminarea = false)
    {
        $whmcsurl = "https://thememetro.com/";
        $localkeydays = 7;
        $allowcheckfaildays = 5;
        $dirpath = dirname(__FILE__);
        if (empty($modulename)) {
            exit("DEBUG ISSUE: Module Name is not provided.");
        }
        $localkeyresult = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->select("value")->where("setting", "licensestatus")->where("module", $modulename)->first();
        $licensekeyresult = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->select("value")->where("setting", "licensekey")->where("module", $modulename)->first();
        $localkey = $localkeyresult->value;
        $licensekey = $licensekeyresult->value;
        if (trim($licensekey) == "") {
            $results["status"] = "Invalid";
            $results["modulename"] = $modulename;
            $results["labeltype"] = "danger";
            $results["licensestatus"] = "You must enter a license key";
            return $results;
        }
        $check_token = time() . md5(mt_rand(1000000000, 0) . $licensekey);
        $checkdate = date("Ymd");
        $domain = $_SERVER["SERVER_NAME"];
        $usersip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["LOCAL_ADDR"];
        $dirpath = explode("/", $dirpath);
        foreach ($dirpath as $key => $value) {
            if ($key != 0) {
                $dirpatha .= "/";
            }
            if ($value == "servers") {
                $dirpatha .= "addons";
            } else {
                if ($value == "gateways") {
                    $dirpatha .= "addons";
                } else {
                    if ($value == "security") {
                        $dirpatha .= "addons";
                    } else {
                        $dirpatha .= $value;
                    }
                }
            }
        }
        $dirpath = $dirpatha;
        $versioncheck = false;
        $moduleconf = $modulename . "_config";
        if (function_exists($moduleconf) && !isset($_SESSION[$modulename . "_version"])) {
            $moduleconfig = $moduleconf("license");
            $version = $moduleconfig["version"];
            $postfields["version"] = $version;
            if ($version != "") {
                $versioncheck = true;
            }
        }
        $verifyfilepath = "modules/servers/licensing/verify.php";
        $localkeyvalid = false;
        if ($localkey) {
            $localkey = str_replace("\n", "", $localkey);
            $localdata = substr($localkey, 0, strlen($localkey) - 32);
            $md5hash = substr($localkey, strlen($localkey) - 32);
            if ($md5hash == md5($localdata . $licensing_secret_key)) {
                $localdata = strrev($localdata);
                $md5hash = substr($localdata, 0, 32);
                $localdata = substr($localdata, 32);
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults["checkdate"];
                if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                    $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                    if ($localexpiry < $originalcheckdate) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(",", $results["validdomain"]);
                        if (!in_array($_SERVER["SERVER_NAME"], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = [];
                        }
                        $validips = explode(",", $results["validip"]);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = [];
                        }
                        $validdirs = explode(",", $results["validdirectory"]);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = [];
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
            $responseCode = 0;
            $postfields = ["licensekey" => $licensekey, "domain" => $domain, "ip" => $usersip, "dir" => $dirpath, "version" => $version];
            if ($check_token) {
                $postfields["check_token"] = $check_token;
            }
            $query_string = "";
            foreach ($postfields as $k => $v) {
                $query_string .= $k . "=" . urlencode($v) . "&";
            }
            if (function_exists("curl_exec")) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            } else {
                $responseCodePattern = "/^HTTP\\/\\d+\\.\\d+\\s+(\\d+)/";
                $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
                if ($fp) {
                    $newlinefeed = "\r\n";
                    $header = "POST " . $whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                    $header .= "Host: " . $whmcsurl . $newlinefeed;
                    $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                    $header .= "Content-length: " . @strlen($query_string) . $newlinefeed;
                    $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                    $header .= $query_string;
                    $data = $line = "";
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    while (!@feof($fp) && $status) {
                        $line = @fgets($fp, 1024);
                        $patternMatches = [];
                        if (!$responseCode && preg_match($responseCodePattern, trim($line), $patternMatches)) {
                            $responseCode = empty($patternMatches[1]) ? 0 : $patternMatches[1];
                        }
                        $data .= $line;
                        $status = @socket_get_status($fp);
                    }
                    @fclose($fp);
                }
            }
            if ($responseCode != 200) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
                if ($localexpiry < $originalcheckdate) {
                    $results = $localkeyresults;
                } else {
                    $results = [];
                    $results["status"] = "Invalid";
                    $results["description"] = "Remote Check Failed";
                    return $results;
                }
            } else {
                preg_match_all("/<(.*?)>([^<]+)<\\/\\1>/i", $data, $matches);
                $results = [];
                foreach ($matches[1] as $k => $v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if (!is_array($results)) {
                exit("Invalid License Server Response");
            }
            if ($results["md5hash"] && $results["md5hash"] != md5($licensing_secret_key . $check_token)) {
                $results["status"] = "Invalid";
                $results["description"] = "MD5 Checksum Verification Failed";
                return $results;
            }
            if ($results["status"] == "Active") {
                $results["checkdate"] = $checkdate;
                $data_encoded = serialize($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
                $data_encoded = wordwrap($data_encoded, 80, "\n", true);
                $results["localkey"] = $data_encoded;
            }
            $results["remotecheck"] = true;
        }
        $results["licensekey"] = $licensekey;
        if (trim($licensekey) == "") {
            $results["licensestatus"] = "Please enter your license key";
        } else {
            $results["licensestatus"] = $results["status"];
            $lastversion = $results["version"];
            if ($versioncheck && trim($lastversion) == "") {
                $versioncheck = false;
                unset($results["localkey"]);
            }
            if ($versioncheck && $version == "") {
                $results["status"] = "Invalid";
                $results["licensestatus"] = "Version Error";
                unset($results["localkey"]);
            }
            if ($versioncheck && $lastversion < $version) {
                $results["status"] = "Invalid";
                $results["licensestatus"] = "You can not update your license without an active support and updates addon.";
                unset($results["localkey"]);
            }
            unset($results["customfields"]);
        }
        if ($results["localkey"]) {
            $updateLicense = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", $modulename)->where("setting", "licensestatus")->update(["value" => $results["localkey"]]);
        } else {
            $results["localkey"] = $localkey;
        }
        if (strtolower($results["status"]) == "active") {
            $results["labeltype"] = "success";
        } else {
            $results["labeltype"] = "danger";
        }
        $results["modulename"] = $modulename;
        unset($postfields);
        unset($data);
        unset($matches);
        unset($whmcsurl);
        unset($licensing_secret_key);
        unset($checkdate);
        unset($usersip);
        unset($localkeydays);
        unset($allowcheckfaildays);
        unset($md5hash);
        return $results;
    }
}
$licensestatus = ws_license_adminpanel("33c21317f60b7f6c1a2b291c678c842c", "adminpanel", true);
$licses = "ws_license_" . basename(dirname(__FILE__)) . "_" . basename(__FILE__, ".php");
if (empty($_SESSION[$licses]) && !empty($licses)) {
    $_SESSION[$licses] = $licensestatus;
}
function ws_importdbtables_adminpanel()
{
    global $s_s_pages;
    $pageb = "<?php\r\n\r\nuse WHMCS\\ClientArea;\r\nuse WHMCS\\Database\\Capsule;\r\n\r\ndefine(\"CLIENTAREA\", true);\r\nrequire __DIR__ . \"/init.php\";\r\n\$ca = new ClientArea();\r\n\$ca->initPage();\r\n\$filename = basename(__FILE__);\r\n\$item = adminpanel_getpagedata(\$filename);\r\nif(empty(\$item)){\r\n    redir(\"\",\"index.php\");\r\n}\r\nif(\$item->publish == \"0\" && \$item->page_url != \"index.php\"){\r\n    redir(\"\",\"index.php\");\r\n}\r\n\$pagedata = adminpanel_getpagesubdata(\$item->id);\r\n\$pagedata[\"item\"] = (array) \$item;\r\nrequire ROOTDIR . \"/modules/addons/adminpanel/core/FrontPageBuilder.php\";\r\n\$ca->setPageTitle(\$item->name);\r\n\$ca->addToBreadCrumb(\"index.php\", Lang::trans(\"globalsystemname\"));\r\n\$ca->addToBreadCrumb(\$filename, \$item->name);\r\n\$pagebuilder = new FrontPageBuilder(html_entity_decode(\$item->description));\r\n\$pagecontent = \$pagebuilder->output();\r\n\$ca->assign(\"pagebuilder\", \$pagecontent);\r\n\$ca->assign(\"pagedata\", \$pagedata);\r\nif(!empty(\$pagedata[\"clientonly\"]) && \$item->page_url != \"index.php\"){\r\n    \$ca->requireLogin();\r\n}\r\n\$ca->assign(\"skipMainBodyContainer\", true);\r\n\$ca->setTemplate(\"pagebuilder\");\r\n\$ca->output();\r\n";
    $installpages = ["webhosting.php", "resellerhosting.php", "vpshosting.php", "dedicated.php", "aboutus.php"];
    foreach ($installpages as $page) {
        $filename = ROOTDIR . "/" . $page;
        if (!file_exists($filename)) {
            file_put_contents($filename, $pageb);
        }
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_config")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_config", function ($table) {
            $table->string("page");
            $table->text("setting");
            $table->longText("value");
        });
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_pages")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_pages", function ($table) {
            $table->increments("id");
            $table->string("name");
            $table->longText("description");
            $table->string("language")->nullable();
            $table->string("page_type");
            $table->string("page_url")->nullable();
            $table->enum("publish", ["1", "0"]);
            $table->timestamps();
        });
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_cmegamenu", function ($table) {
            $table->increments("id");
            $table->string("relid");
            $table->text("setting");
            $table->longText("value");
        });
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_megamenupages", function ($table) {
            $table->increments("id");
            $table->string("name");
            $table->longText("description");
            $table->string("language")->nullable();
            $table->string("page_type");
            $table->string("page_url")->nullable();
            $table->enum("publish", ["1", "0"]);
            $table->timestamps();
        });
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cpages")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_cpages", function ($table) {
            $table->increments("id");
            $table->string("relid");
            $table->text("setting");
            $table->longText("value");
        });
        $installer = ["config", "cpages", "cmegamenu", "pages", "megamenupages"];
        global $CONFIG;
        foreach ($installer as $sqlfile) {
            $sql = file_get_contents(__DIR__ . "/core/installer/" . $sqlfile . ".sql");
            $sql = str_replace("{SYSTEMURL}", $CONFIG["SystemURL"], $sql);
            $sql = str_replace("{SYSTEMTEMPLATE}", $CONFIG["Template"], $sql);
            Illuminate\Database\Capsule\Manager::connection()->getPdo()->exec($sql);
        }
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_sitemap")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_sitemap", function ($table) {
            $table->string("option_name");
            $table->longText("value");
        });
        $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("page_type", "pages")->get();
        foreach ($items as $item) {
            if (!in_array($item->value, ["#"])) {
                if ($item->page_url != "index.php") {
                    $custompagename = str_replace(".php", "", $item->page_url);
                    $s_s_pages[] = ["name" => [$custompagename, $custompagename], "value" => ["add" => "1", "priority" => "0.4", "frequency" => "yearly"]];
                }
            }
        }
        foreach ($s_s_pages as $s_s_page) {
            if (!get_query_val("mod_adminpanel_sitemap", "option_name", ["option_name" => $s_s_page["name"][0]])) {
                insert_query("mod_adminpanel_sitemap", ["option_name" => $s_s_page["name"][0], "value" => serialize($s_s_page["value"])]);
            }
        }
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_sitemap_custom_links")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_sitemap_custom_links", function ($table) {
            $table->increments("ID");
            $table->string("value");
        });
    }
    if (!Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_paymentgateway")) {
        Illuminate\Database\Capsule\Manager::schema()->create("mod_adminpanel_paymentgateway", function ($table) {
            $table->increments("id");
            $table->string("gateway");
            $table->longText("countryincluded");
            $table->longText("countryexcluded");
            $table->string("minamount");
            $table->string("maxamount");
        });
    }
}
function adminpanel_config()
{
    if ($vars != "license") {
        global $licensestatus;
    }
    $configarray = ["name" => "Croster Panel", "description" => "This addon provide you the access to page manager, menu manager & control the theme color & layout of Croster.", "version" => "2.0.10", "author" => "<a href='https://thememetro.com' target='_blank'><img src='https://thememetro.com/images/logo.png' width='100px'></a>", "language" => "english", "fields" => ["nodeletedb" => ["FriendlyName" => "Database Table", "Type" => "yesno", "Size" => "25", "Description" => "Check if the database of the module should be refreshed after deactivating the Croster Panel addon."], "licensekey" => ["FriendlyName" => "License key", "Type" => "text", "Size" => "35", "Description" => ""], "licensekeyinfo" => ["FriendlyName" => "", "Description" => "<div class='alert alert-warning' style='margin: 0;'>If you change the license key all settings, configuration and pages will get deleted permanently and it would become fresh install. You can only upgrade or downgrade license without any deletion like from Leased license to Own license or Own license to Leased license. If you want to change Leased license billing cycle from monthly to annually please <a href='https://thememetro.com/submitticket.php?step=2&deptid=2' target='_blank'><strong>contact us</strong></a>, we will do it manually without changing license key.</div>"], "licensestatus" => ["FriendlyName" => "License Status", "Description" => "<span class='label label-" . $licensestatus["labeltype"] . "'> " . $licensestatus["licensestatus"] . " </span>"]]];
    if (strtolower($licensestatus["licensestatus"]) == "active") {
        if (isset($_POST["fields"]["adminpanel"]["licensekey"])) {
            $modulename = "adminpanel";
            $licensekeyresult = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->select("value")->where("setting", "licensekey")->where("module", $modulename)->first();
            $licensekey = $licensekeyresult->value;
            $licensekeyexplode = explode("-", trim($licensekey));
            $licensekeystart = $licensekeyexplode[0];
            $postlicensekeyexplode = explode("-", trim($_POST["fields"]["adminpanel"]["licensekey"]));
            $postlicensekeystart = $postlicensekeyexplode[0];
            if (trim($licensekeystart) == trim($postlicensekeystart) && trim($licensekey) != trim($_POST["fields"]["adminpanel"]["licensekey"])) {
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_config");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_pages");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_cpages");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_cmegamenu");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_megamenupages");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_sitemap");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_sitemap_custom_links");
                Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_paymentgateway");
                ws_importdbtables_adminpanel();
            }
        }
        Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->updateOrInsert(["module" => "adminpanel", "setting" => "licensekeycode"], ["module" => "adminpanel", "setting" => "licensekeycode", "value" => md5("33c21317adminpanel")]);
    } else {
        Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->updateOrInsert(["module" => "adminpanel", "setting" => "licensekeycode"], ["module" => "adminpanel", "setting" => "licensekeycode", "value" => ""]);
    }
    return $configarray;
}
function adminpanel_activate()
{
    $pageb = "<?php\r\n\r\nuse WHMCS\\ClientArea;\r\nuse WHMCS\\Database\\Capsule;\r\n\r\ndefine(\"CLIENTAREA\", true);\r\nrequire __DIR__ . \"/init.php\";\r\n\$ca = new ClientArea();\r\n\$ca->initPage();\r\n\$filename = basename(__FILE__);\r\n\$item = adminpanel_getpagedata(\$filename);\r\nif(empty(\$item)){\r\n    redir(\"\",\"index.php\");\r\n}\r\nif(\$item->publish == \"0\" && \$item->page_url != \"index.php\"){\r\n    redir(\"\",\"index.php\");\r\n}\r\n\$pagedata = adminpanel_getpagesubdata(\$item->id);\r\n\$pagedata[\"item\"] = (array) \$item;\r\nrequire ROOTDIR . \"/modules/addons/adminpanel/core/FrontPageBuilder.php\";\r\n\$ca->setPageTitle(\$item->name);\r\n\$ca->addToBreadCrumb(\"index.php\", Lang::trans(\"globalsystemname\"));\r\n\$ca->addToBreadCrumb(\$filename, \$item->name);\r\n\$pagebuilder = new FrontPageBuilder(html_entity_decode(\$item->description));\r\n\$pagecontent = \$pagebuilder->output();\r\n\$ca->assign(\"pagebuilder\", \$pagecontent);\r\n\$ca->assign(\"pagedata\", \$pagedata);\r\nif(!empty(\$pagedata[\"clientonly\"]) && \$item->page_url != \"index.php\"){\r\n    \$ca->requireLogin();\r\n}\r\n\$ca->assign(\"skipMainBodyContainer\", true);\r\n\$ca->setTemplate(\"pagebuilder\");\r\n\$ca->output();\r\n";
    ws_importdbtables_adminpanel();
    return ["status" => "success", "description" => "Croster Panel has been activated."];
}
function adminpanel_deactivate()
{
    $delete = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", "adminpanel")->where("setting", "nodeletedb")->first();
    if ($delete->value) {
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_config");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_pages");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_cpages");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_cmegamenu");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_megamenupages");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_sitemap");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_sitemap_custom_links");
        Illuminate\Database\Capsule\Manager::schema()->dropIfExists("mod_adminpanel_paymentgateway");
    }
    return ["status" => "success", "description" => "Croster Panel has been deactivated, and table(s) has been removed from your database."];
}
function adminpanel_output($vars)
{
    global $licensestatus;
    $licses = "ws_license_" . basename(dirname(__FILE__)) . "_" . basename(__FILE__, ".php");
    if (empty($licensestatus) || !$licensestatus) {
        $licensestatus = $_SESSION[$licses];
    }
    unset($_SESSION[$licses]);
    if (strtolower($licensestatus["status"]) != "active") {
        echo "<div class=\"alert alert-danger\"><h4><strong>License Error!</strong></h4><p>Possible reasons for this issue include:</p><ul><li>You recently updated Croster, but you need to click on the <strong>Save Changes</strong> button in System Settings &gt;&gt; Addon Modules &gt;&gt; Croster Panel &gt;&gt; Configure.</li><li>The IP address, directory, or both of your installation has changed. To fix this, you must reissue the license.</li><li>The license key has been entered incorrectly.</li><li>Your license payment is overdue.</li><li>Your license has been suspended for use on a banned domain.</li><li>Your license was found to be used against the End User License Agreement.</li></ul><p>If you believe this message is in error, please contact us at <a href=\"https://thememetro.com/submitticket.php\" target=\"_blank\">www.ThemeMetro.com</a>.</p></div>";
        return "";
    }
    $AdminPanelConfig = "configurations.php";
    if (!class_exists("AdminPanelLoader")) {
        require __DIR__ . "/AdminPanelLoader.php";
    }
    $AdminClass = new AdminPanelLoader($AdminPanelConfig);
    $AdminClass->RunPage();
    return "";
}

?>