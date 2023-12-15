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
define("AdminPanelModuleRoot", __DIR__);
$isDevLicense = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", basename(__DIR__))->where("setting", "licensekey")->where("value", "LIKE", "dev%")->first();
if (0 < !empty($isDevLicense) && !function_exists("TMDevLicenseBannerAndText")) {
    function TMDevLicenseBannerAndText()
    {
        $isDevLicense = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", basename(__DIR__))->where("setting", "licensekey")->where("value", "LIKE", "dev%")->first();
        if (0 < !empty($isDevLicense)) {
            add_hook("ClientAreaFooterOutput", 1, function ($vars) {
                global $smarty;
                $activeTemplate = $smarty->getVariable("template");
                if (file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
                    return "<script src=\"https://thememetro.com/dev/dev.js\"></script>";
                }
            });
        }
    }
    TMDevLicenseBannerAndText();
}
$isTRLicense = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", basename(__DIR__))->where("setting", "licensekey")->where("value", "LIKE", "")->first();
echo "isTRLicense";
print_r($isTRLicense);
if (0 < !empty($isTRLicense) && !function_exists("TMEmpLicenseBannerAndText")) {
    function TMEmpLicenseBannerAndText()
    {
        $isTRLicense = Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", basename(__DIR__))->where("setting", "licensekey")->where("value", "LIKE", "")->first();
        if (0 < !empty($isTRLicense)) {
            add_hook("ClientAreaFooterOutput", 1, function ($vars) {
                return "<script src=\"https://thememetro.com/dev/trial.js\"></script>";
            });
        }
    }
    TMEmpLicenseBannerAndText();
}
add_hook("AfterCalculateCartTotals", 0, function ($vars) {
    $_SESSION["tempcarttotal"] = $vars["rawtotal"];
});
add_hook("ClientAreaPageCart", 0, function ($vars) {
    $settings = adminpanel_configurations();
    if ($settings["orderform"]["orderopc"] == "1" && !isset($_SESSION["uid"]) && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_paymentgateway")) {
        $gateways = $vars["gateways"];
        $clientcountry = $vars["clientsdetails"]["country"];
        $ordertotal = $_SESSION["tempcarttotal"];
        $hasrecords = 0;
        if (!empty($gateways)) {
            foreach ($gateways as $gateway => $gatewaydata) {
                $saveditem = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_paymentgateway")->where("gateway", $gateway)->first();
                if (!empty($saveditem)) {
                    $hasrecords = 1;
                    if (unserialize($saveditem->countryexcluded) && in_array($clientcountry, unserialize($saveditem->countryexcluded))) {
                        unset($gateways[$gateway]);
                    }
                    if (unserialize($saveditem->countryincluded) && !in_array($clientcountry, unserialize($saveditem->countryincluded))) {
                        unset($gateways[$gateway]);
                    }
                    if (0 < $saveditem->minamount && $ordertotal < $saveditem->minamount) {
                        unset($gateways[$gateway]);
                    }
                    if (0 < $saveditem->maxamount && $saveditem->maxamount <= $ordertotal) {
                        unset($gateways[$gateway]);
                    }
                }
            }
        }
        return ["gateways" => $gateways];
    }
});
add_hook("ClientAreaFooterOutput", 2, function ($vars) {
    global $CONFIG;
    $clang = $CONFIG["Language"];
    if (isset($_SESSION["uid"])) {
        $litem = Illuminate\Database\Capsule\Manager::table("tblclients")->where("id", $_SESSION["uid"])->where("language", "<>", "")->value("language");
        if ($litem) {
            $clang = $litem;
        }
    }
    if (isset($_SESSION["Language"])) {
        $clang = $_SESSION["Language"];
    }
    $clang = strtolower($clang);
    $settings = adminpanel_configurations();
    $lessvariables = getlessvariables();
    $currentUser = new WHMCS\Authentication\CurrentUser();
    $cuser = $currentUser->user();
    $ContactFormRedirect = $CONFIG["ContactFormDept"];
    $registrationEnabled = $CONFIG["AllowClientRegister"];
    global $smarty;
    foreach ($settings as $setting => $values) {
        if (!empty($values["langsave"])) {
            foreach ($values["langsave"] as $key => $value) {
                $settings[$setting][$value] = $settings[$setting][$value . "_" . $CONFIG["Language"]];
                if (isset($settings[$setting][$value . "_" . $clang]) && $settings[$setting][$value . "_" . $clang] != "") {
                    $settings[$setting][$value] = $settings[$setting][$value . "_" . $clang];
                }
            }
        }
    }
    $smarty->assign("themesettings", $settings);
    $smarty->assign("lessvariables", $lessvariables);
    $smarty->assign("cuser", $cuser);
    $smarty->assign("ContactFormRedirect", $ContactFormRedirect);
    $smarty->assign("registrationEnabled", $registrationEnabled);
    $footermenu = adminpanel_getmenu("footermenu");
    foreach ($footermenu as $key => $value) {
        if (isset($value["data"]["menu_name_" . $clang]) && $value["data"]["menu_name_" . $clang]) {
            $footermenu[$key]["name"] = $value["data"]["menu_name_" . $clang];
        }
        if (isset($value["children"]) && !empty($value["children"])) {
            foreach ($value["children"] as $ckey => $cvalue) {
                if (isset($cvalue["data"]["menu_name_" . $clang]) && $cvalue["data"]["menu_name_" . $clang]) {
                    $footermenu[$key]["children"][$ckey]["name"] = $cvalue["data"]["menu_name_" . $clang];
                }
            }
        }
    }
    $smarty->assign("footermenu", $footermenu);
    $activeOrderForm = $smarty->getVariable("carttpl");
    if (file_exists(__DIR__ . "/../../../templates/orderforms/" . $activeOrderForm . "/croster.tpl")) {
        if ($vars["templatefile"] == "domainregister" || $vars["templatefile"] == "domaintransfer") {
            $smarty->assign("pagetype", "custom");
        }
    } else {
        if ($vars["inShoppingCart"]) {
            $sidebarshadow = "<script>jQuery(document).ready(function() {\n    jQuery('.card-sidebar').addClass('no-shadow');\n});</script>";
            return $sidebarshadow;
        }
    }
});
add_hook("ClientAreaPrimaryNavbar", 1, function (WHMCS\View\Menu\Item $primaryNavbar) {
    global $smarty;
    $activeTemplate = $smarty->getVariable("template");
    $settings = adminpanel_configurations();
    if (file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
        global $CONFIG;
        $friendlyurl = $CONFIG["RouteUriPathMode"];
        if ($friendlyurl == "acceptpathinfo") {
            $urlpath = "index.php/";
            $urlpathstore = "index.php/store/";
        } else {
            if ($friendlyurl == "rewrite") {
                $urlpath = "/";
                $urlpathstore = "store/";
            } else {
                if ($friendlyurl == "basic") {
                    $urlpath = "index.php?rp=/";
                    $urlpathstore = "index.php?rp=/store/";
                }
            }
        }
        $marketconnect = Illuminate\Database\Capsule\Manager::table("tblmarketconnect_services")->where("status", "1")->get();
        $client = Menu::context("client");
        $menus = adminpanel_getmenu("menu");
        if (Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu") && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
            $enablemegamenu = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", "enablemegamenu")->value("value");
        }
        $smarty->assign("enablemegamenu", $enablemegamenu);
        if ($enablemegamenu) {
            $removes = ["Home", "Services", "Affiliates", "Open Ticket", "Website Security", "Support", "Domains", "Announcements", "Knowledgebase", "Network Status", "Contact Us", "Billing", "Store"];
        } else {
            if ($settings["headersetting"]["disablehomeicon"] == "1" && is_null($client)) {
                $removes = ["Home", "Services", "Affiliates", "Open Ticket", "Website Security", "Support", "Domains", "Announcements", "Knowledgebase", "Network Status", "Contact Us", "Billing", "Store"];
            } else {
                $navItem = $primaryNavbar->getChild("Home");
                if (is_null($navItem)) {
                    return NULL;
                }
                $navItem->setOrder(0);
                if (is_null($client)) {
                    if ($settings["headersetting"]["CustomLogoLink"] !== "") {
                        $navItem->setUri($settings["headersetting"]["CustomLogoLink"]);
                    } else {
                        $navItem->setUri($CONFIG["SystemURL"]);
                    }
                } else {
                    $navItem->setUri("clientarea.php");
                }
                if ($settings["headersetting"]["enablehomeicon"] == "1") {
                    $navItem->setIcon("fa-home");
                }
                $removes = ["Services", "Affiliates", "Open Ticket", "Website Security", "Support", "Domains", "Announcements", "Knowledgebase", "Network Status", "Contact Us", "Billing", "Store"];
            }
        }
        foreach ($removes as $value) {
            if (!is_null($primaryNavbar->getChild($value))) {
                $primaryNavbar->removeChild($value);
            }
        }
        if (!empty($menus)) {
            global $CONFIG;
            $clang = $CONFIG["Language"];
            if (isset($_SESSION["uid"])) {
                $litem = Illuminate\Database\Capsule\Manager::table("tblclients")->where("id", $_SESSION["uid"])->where("language", "<>", "")->value("language");
                if ($litem) {
                    $clang = $litem;
                }
            }
            if (isset($_SESSION["Language"])) {
                $clang = $_SESSION["Language"];
            }
            $clang = strtolower($clang);
            foreach ($menus as $key => $value) {
                if (!(is_null($client) && $value["logged_only"] == "2")) {
                    if (is_null($client) || $value["logged_only"] != "3") {
                        if ($value["data"]["enabled"] == "1") {
                            $pname = $value["name"];
                            if (isset($value["data"]["menu_name_" . $clang]) && $value["data"]["menu_name_" . $clang] != "") {
                                $pname = $value["data"]["menu_name_" . $clang];
                            }
                            $mana = [];
                            if (isset($value["data"]["icon"]) && $value["data"]["icon"] != "") {
                                $mana["icon"] = $value["data"]["icon"];
                            }
                            if (isset($value["data"]["class"]) && $value["data"]["class"] != "") {
                                $mana["class"] = $value["data"]["class"];
                            }
                            if ($value["newtab"]) {
                                $mana["attributes"] = ["target" => "_blank"];
                            }
                            if (!is_null($primaryNavbar->addChild($pname, $mana))) {
                                $navItem = $primaryNavbar->getChild($pname);
                                if (is_null($navItem)) {
                                    return NULL;
                                }
                                if (isset($value["data"]["class"]) && $value["data"]["class"] != "") {
                                    $navItem->setClass($value["data"]["class"]);
                                }
                                if (isset($value["data"]["parentclass"]) && $value["data"]["parentclass"] != "") {
                                    $navItem->setExtras(["parentclass" => $value["data"]["parentclass"]]);
                                }
                                if (!empty($value["children"])) {
                                    foreach ($value["children"] as $ckey => $cvalue) {
                                        if (!(is_null($client) && $cvalue["data"]["logged_only"] == "2")) {
                                            if (is_null($client) || $cvalue["data"]["logged_only"] != "3") {
                                                if ($cvalue["data"]["enabled"] == "1") {
                                                    $cname = $cvalue["name"];
                                                    if (isset($cvalue["data"]["menu_name_" . $clang]) && $cvalue["data"]["menu_name_" . $clang] != "") {
                                                        $cname = $cvalue["data"]["menu_name_" . $clang];
                                                    }
                                                    if (isset($cvalue["data"]["menu_subtitle_" . $clang]) && $cvalue["data"]["menu_subtitle_" . $clang] != "") {
                                                        $cname .= "<span>" . $cvalue["data"]["menu_subtitle_" . $clang] . "</span>";
                                                    } else {
                                                        if (isset($cvalue["data"]["menu_subtitle"]) && strlen($cvalue["data"]["menu_subtitle"])) {
                                                            $cname .= "<span>" . $cvalue["data"]["menu_subtitle"] . "</span>";
                                                        }
                                                    }
                                                    $newtab = [];
                                                    if ($cvalue["newtab"]) {
                                                        $newtab = ["target" => "_blank"];
                                                    }
                                                    $primaryNavbar->getChild($pname)->addChild($cname, ["uri" => $cvalue["url"], "attributes" => $newtab, "class" => $cvalue["data"]["class"], "order" => $cvalue["order"]]);
                                                    $navItem1 = $primaryNavbar->getChild($pname)->getChild($cname);
                                                    if (!is_null($navItem1)) {
                                                        if (isset($cvalue["data"]["class"]) && $cvalue["data"]["class"] != "") {
                                                            $navItem1->setClass($cvalue["data"]["class"]);
                                                        }
                                                        if (isset($cvalue["data"]["icon"]) && $cvalue["data"]["icon"] != "") {
                                                            $navItem1->setIcon($cvalue["data"]["icon"]);
                                                        }
                                                        if (isset($cvalue["data"]["parentclass"]) && $cvalue["data"]["parentclass"] != "") {
                                                            $navItem1->setExtras(["parentclass" => $cvalue["data"]["parentclass"]]);
                                                        }
                                                    }
                                                    if (isset($cvalue["data"]["custom_html_" . $clang]) && $cvalue["data"]["custom_html_" . $clang] != "") {
                                                        $navItem1 = $primaryNavbar->getChild($pname)->getChild($cname)->setBadge($cvalue["data"]["custom_html_" . $clang]);
                                                    } else {
                                                        if (isset($cvalue["data"]["custom_html"]) && strlen($cvalue["data"]["custom_html"])) {
                                                            $navItem1 = $primaryNavbar->getChild($pname)->getChild($cname)->setBadge($cvalue["data"]["custom_html"]);
                                                        }
                                                    }
                                                    if (!empty($cvalue["children"])) {
                                                        foreach ($cvalue["children"] as $innkey => $innvalue) {
                                                            if (!(is_null($client) && $innvalue["data"]["logged_only"] == "2")) {
                                                                if (is_null($client) || $innvalue["data"]["logged_only"] != "3") {
                                                                    if ($innvalue["data"]["enabled"] == "1") {
                                                                        $inname = $innvalue["name"];
                                                                        if (isset($innvalue["data"]["menu_name_" . $clang]) && $innvalue["data"]["menu_name_" . $clang] != "") {
                                                                            $inname = $innvalue["data"]["menu_name_" . $clang];
                                                                        }
                                                                        if (isset($innvalue["data"]["menu_subtitle_" . $clang]) && $innvalue["data"]["menu_subtitle_" . $clang] != "") {
                                                                            $inname .= "<span>" . $innvalue["data"]["menu_subtitle_" . $clang] . "</span>";
                                                                        } else {
                                                                            if (isset($innvalue["data"]["menu_subtitle"]) && strlen($innvalue["data"]["menu_subtitle"])) {
                                                                                $inname .= "<span>" . $innvalue["data"]["menu_subtitle"] . "</span>";
                                                                            }
                                                                        }
                                                                        $newtab = [];
                                                                        if ($innvalue["newtab"]) {
                                                                            $newtab = ["target" => "_blank"];
                                                                        }
                                                                        $primaryNavbar->getChild($pname)->getChild($cname)->addChild($inname, ["uri" => $innvalue["url"], "attributes" => $newtab, "class" => $innvalue["data"]["class"], "order" => $innvalue["order"]]);
                                                                        $navItem1 = $primaryNavbar->getChild($pname)->getChild($cname)->getChild($inname);
                                                                        if (!is_null($navItem1)) {
                                                                            if (isset($innvalue["data"]["class"]) && $innvalue["data"]["class"] != "") {
                                                                                $navItem1->setClass($innvalue["data"]["class"]);
                                                                            }
                                                                            if (isset($innvalue["data"]["icon"]) && $innvalue["data"]["icon"] != "") {
                                                                                $navItem1->setIcon($innvalue["data"]["icon"]);
                                                                            }
                                                                            if (isset($innvalue["data"]["parentclass"]) && $innvalue["data"]["parentclass"] != "") {
                                                                                $navItem1->setExtras(["parentclass" => $innvalue["data"]["parentclass"]]);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $navItem->setOrder($value["order"]);
                                $navItem->setUri($value["url"]);
                            }
                        }
                    }
                }
            }
        }
        if (!is_null($primaryNavbar->getChild("Services")) && !$enablemegamenu) {
            if (!is_null($client) && !empty($marketconnect)) {
                foreach ($marketconnect as $service) {
                    if ($service->name == "symantec") {
                        $primaryNavbar->getChild("Services")->addChild("Manage SSL Certificates", ["label" => Lang::trans("navManageSsl"), "uri" => $urlpathstore . "ssl-certificates/manage", "order" => "2"]);
                    }
                }
            }
            if (!empty($marketconnect)) {
                foreach ($marketconnect as $service) {
                    if ($service->name == "symantec") {
                        $primaryNavbar->getChild("Services")->addChild("SSL Certificates", ["label" => Lang::trans("navMarketConnectService.symantec"), "uri" => $urlpathstore . "ssl-certificates", "order" => "100"]);
                    }
                    if ($service->name == "weebly") {
                        $primaryNavbar->getChild("Services")->addChild("Website Builder", ["label" => Lang::trans("navMarketConnectService.weebly"), "uri" => $urlpathstore . "website-builder", "order" => "110"]);
                    }
                    if ($service->name == "spamexperts") {
                        $primaryNavbar->getChild("Services")->addChild("E-mail Services", ["label" => Lang::trans("navMarketConnectService.spamexperts"), "uri" => $urlpathstore . "email-services", "order" => "120"]);
                    }
                    if ($service->name == "sitelock") {
                        $primaryNavbar->getChild("Services")->addChild("Website Security", ["label" => self::trans("navMarketConnectService.sitelock"), "uri" => $urlpathstore . "sitelock", "order" => "130"]);
                    }
                    if ($service->name == "codeguard") {
                        $primaryNavbar->getChild("Services")->addChild("Website Backup", ["label" => Lang::trans("navMarketConnectService.codeguard"), "uri" => $urlpathstore . "codeguard", "order" => "140"]);
                    }
                    if ($service->name == "nordvpn") {
                        $primaryNavbar->getChild("Services")->addChild("VPN", ["label" => Lang::trans("navMarketConnectService.nordvpn"), "uri" => $urlpathstore . "nordvpn", "order" => "150"]);
                    }
                    if ($service->name == "marketgoo") {
                        $primaryNavbar->getChild("Services")->addChild("Seo Tools", ["label" => Lang::trans("navMarketConnectService.marketgoo"), "uri" => $urlpathstore . "marketgoo", "order" => "160"]);
                    }
                    if ($service->name == "ox") {
                        $primaryNavbar->getChild("Services")->addChild("Professional Email", ["label" => Lang::trans("navMarketConnectService.ox"), "uri" => $urlpathstore . "professional-email", "order" => "170"]);
                    }
                    if ($service->name == "sitebuilder") {
                        $primaryNavbar->getChild("Services")->addChild("Professional Email", ["label" => Lang::trans("navMarketConnectService.siteBuilder"), "uri" => $urlpathstore . "site-builder", "order" => "180"]);
                    }
                    if ($service->name == "xovinow") {
                        $primaryNavbar->getChild("Services")->addChild("XOVI Now", ["label" => Lang::trans("navMarketConnectService.xovinow"), "uri" => $urlpathstore . "xovinow", "order" => "190"]);
                    }
                    if ($service->name == "threesixtymonitoring") {
                        $primaryNavbar->getChild("Services")->addChild("Site & Server Monitoring", ["label" => Lang::trans("navMarketConnectService.threesixtymonitoring"), "uri" => $urlpathstore . "360monitoring", "order" => "200"]);
                    }
                }
            }
        }
    }
});
add_hook("ClientAreaSecondaryNavbar", 1, function (WHMCS\View\Menu\Item $secondaryNavbar) {
    global $smarty;
    $settings = adminpanel_configurations();
    $activeTemplate = $smarty->getVariable("template");
    if (file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
        $client = Menu::context("client");
        if ($settings["clientarea"]["gravatar"] == "1") {
            $accountGreeting = is_null($client) ? "<span class='user-avatar avatar-navbar'><i class='fas fa-user mr-0'></i></span>" : "<span class='user-avatar avatar-small'><img src='//gravatar.com/avatar/" . $hash . "' /></span><span class='user-info'>" . Lang::trans("hello") . ", " . $client->firstName . "!</span>";
        } else {
            $accountGreeting = is_null($client) ? "<span class='user-avatar avatar-navbar'><i class='fas fa-user mr-0'></i></span>" : "<span class='user-avatar avatar-small'><i class='fas fa-user mr-0'></i></span><span class='user-info'>" . Lang::trans("hello") . ", " . $client->firstName . "!</span>";
        }
        $currentUser = new WHMCS\Authentication\CurrentUser();
        $user = $currentUser->user();
        if (!is_null($user)) {
            $address = strtolower(trim($user->email));
            $hash = md5($address);
            if ($settings["clientarea"]["gravatar"] == "1") {
                $accountGreeting = "<span class='user-avatar avatar-navbar'><img src='//gravatar.com/avatar/" . $hash . "' /></span><span class='user-info'>" . Lang::trans("hello") . ", " . $user->firstName . "!</span>";
            } else {
                $accountGreeting = "<span class='user-avatar avatar-navbar'><i class='fas fa-user mr-0'></i></span><span class='user-info'>" . Lang::trans("hello") . ", " . $user->firstName . "!</span>";
            }
        }
        $accountNavbar = $secondaryNavbar->getChild("Account");
        $accountNavbar->setClass("nocaret user_icon");
        $accountNavbar->setLabel($accountGreeting);
    }
});
add_hook("ClientAreaHeadOutput", 1, function ($vars) {
    global $smarty;
    $activeTemplate = $smarty->getVariable("template");
    if (file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl") && $vars["templatefile"] == "viewannouncement") {
        return "<meta name=\"description\" content=\"" . $vars["summary"] . "\">";
    }
});
add_hook("AdminAreaFooterOutput", 1, function ($vars) {
    $settings = adminpanel_configurations();
    if ($settings["megamenu"]["enablemegamenu"] == "1") {
        return "<script>\njQuery(document).ready(function() {\n    jQuery('#DefaultMenu').addClass('hidden');\n});\n</script>";
    }
});
add_hook("ClientAreaPage", 1, "loginpages_announcements_hook");
if (App::getCurrentFilename() == "submitticket" || App::getCurrentFilename() == "contact") {
    add_hook("ClientAreaSecondarySidebar", 1, function (WHMCS\View\Menu\Item $secondarySidebar) {
        global $smarty;
        global $_LANG;
        $activeTemplate = $smarty->getVariable("template");
        $settings = adminpanel_configurations();
        global $CONFIG;
        $clang = $CONFIG["Language"];
        if (isset($_SESSION["Language"])) {
            $clang = $_SESSION["Language"];
        }
        $clang = strtolower($clang);
        if ($settings["clientarea"]["supportnotice"] == "1" && file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
            $supportHours = $secondarySidebar->addChild("Support Hours");
            if ($settings["clientarea"]["supportnoticepaneltitle_" . $clang]) {
                $supportHours->setLabel("" . $settings["clientarea"]["supportnoticepaneltitle_" . $clang]);
            }
            $supportHours->setClass("panel-info panel-support-hours");
            $supportHours->setIcon("fa-life-ring");
            $supportHours->moveToFront();
            $timeZone = "<small>(" . date_default_timezone_get() . ")</small>";
            $StartTime = "" . $settings["clientarea"]["starthour"] . "";
            $EndTime = "" . $settings["clientarea"]["endinghour"] . "";
            if ($StartTime !== $EndTime) {
                if ($settings["clientarea"]["railtimezone"] == "1") {
                    $sTime = date("H:i", strtotime("" . $StartTime . ""));
                    $eTime = date("H:i", strtotime("" . $EndTime . ""));
                } else {
                    $sTime = "" . $settings["clientarea"]["starthour"] . "";
                    $eTime = "" . $settings["clientarea"]["endinghour"] . "";
                }
                $supportHours->addChild("<span>" . $sTime . " - " . $eTime . "</span><span>" . $timeZone . "</span>", ["order" => 1]);
            }
            if ($settings["clientarea"]["weekend"]) {
                $weekends = implode(",", $settings["clientarea"]["weekend"]);
                $offdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                $offdaysrep = ["" . $_LANG["dateTime"]["sunday"] . "", "" . $_LANG["dateTime"]["monday"] . "", "" . $_LANG["dateTime"]["tuesday"] . "", "" . $_LANG["dateTime"]["wednesday"] . "", "" . $_LANG["dateTime"]["thursday"] . "", "" . $_LANG["dateTime"]["friday"] . "", "" . $_LANG["dateTime"]["saturday"] . ""];
                $days = str_replace($offdays, $offdaysrep, $weekends);
                $supportHours->addChild("<strong>" . $_LANG["closed"] . ":</strong> " . $days . "", ["order" => 2]);
            }
            $client = Menu::context("client");
            $greeting = is_null($client) ? "" : "" . Lang::trans("hello") . ", <b>" . $client->firstName . "</b>!";
            $currentUser = new WHMCS\Authentication\CurrentUser();
            $user = $currentUser->user();
            if (!is_null($user)) {
                $greeting = "" . Lang::trans("hello") . ", <b>" . $user->firstName . "</b>!";
            }
            $now = Carbon\Carbon::now();
            $weekdays = ["1" => "Monday", "2" => "Tuseday", "3" => "Wednesday", "4" => "Thursday", "5" => "Friday", "6" => "Saturday", "7" => "Sunday"];
            if ($settings["clientarea"]["weekend"]) {
                if (!in_array($weekdays[date("N")], $settings["clientarea"]["weekend"])) {
                    $current_time = date("h:i a");
                    $starthour = ltrim($settings["clientarea"]["starthour"], "0");
                    $endinghour = ltrim($settings["clientarea"]["endinghour"], "0");
                    $date1 = strtotime(date("h:i a", strtotime($current_time)));
                    $date2 = strtotime(date("h:i a", strtotime($starthour)));
                    $date3 = strtotime(date("h:i a", strtotime($endinghour)));
                    if ($date2 <= $date1 && $date1 <= $date3 || $StartTime == $EndTime) {
                        $supportIsOpen = 1;
                    } else {
                        $supportIsOpen = 0;
                    }
                }
            } else {
                $current_time = date("h:i a");
                $starthour = ltrim($settings["clientarea"]["starthour"], "0");
                $endinghour = ltrim($settings["clientarea"]["endinghour"], "0");
                $date1 = strtotime(date("h:i a", strtotime($current_time)));
                $date2 = strtotime(date("h:i a", strtotime($starthour)));
                $date3 = strtotime(date("h:i a", strtotime($endinghour)));
                if ($date2 <= $date1 && $date1 <= $date3 || $StartTime == $EndTime) {
                    $supportIsOpen = 1;
                } else {
                    $supportIsOpen = 0;
                }
            }
            $openmessage = "<div class='w-100 text-muted'><span class='label status label-success text-uppercase mb-2'>" . $_LANG["serverstatusonline"] . "</span>" . $greeting . " We're open and will respond to your ticket soon!</div>";
            if ($settings["clientarea"]["supportonlinenotice_" . $clang]) {
                $openmessage = "<div class='w-100 text-muted'><span class='label status label-success text-uppercase mb-2'>" . $_LANG["serverstatusonline"] . "</span>" . $greeting . " " . $settings["clientarea"]["supportonlinenotice_" . $clang] . "</div>";
            }
            $closemessage = "<div class='w-100 text-muted'><span class='label status label-danger text-uppercase mb-2'>" . $_LANG["serverstatusoffline"] . "</span>" . $greeting . " We will respond on the next business day. Sit tight!</div>";
            if ($settings["clientarea"]["supportofflinenotice_" . $clang]) {
                $closemessage = "<div class='w-100 text-muted'><span class='label status label-danger text-uppercase mb-2'>" . $_LANG["serverstatusoffline"] . "</span>" . $greeting . " " . $settings["clientarea"]["supportofflinenotice_" . $clang] . "</div>";
            }
            $supportHours->setFooterHtml($supportIsOpen ? $openmessage : $closemessage);
        }
    });
}
add_hook("ClientAreaPrimarySidebar", 1, function (WHMCS\View\Menu\Item $primarySidebar) {
    $tid = filter_input(INPUT_GET, "tid", FILTER_SANITIZE_NUMBER_INT);
    if (!empty($tid) && ctype_digit($tid)) {
        $relatedService = Illuminate\Database\Capsule\Manager::table("tbltickets")->where("tid", $tid)->value("service");
        if (!empty($relatedService)) {
            $serviceType = substr($relatedService, 0, 1);
            $relatedService = substr($relatedService, 1);
            if ($serviceType == "D") {
                $domain = Illuminate\Database\Capsule\Manager::table("tbldomains")->where("id", $relatedService)->value("domain");
                if (!empty($domain)) {
                    $url = "clientarea.php?action=domaindetails&id=" . $relatedService;
                    $icon = "<i class=\"fas fa-globe fa-fw\" style=\"float:none;\"></i>";
                    $label = $icon . " <a href=\"" . $url . "\">" . $domain . "</a>";
                    $primarySidebar->getChild("Ticket Information")->addChild("Related Service")->setClass("ticket-details-children")->setLabel("<span class=\"title\">" . Lang::trans("relatedservice") . "</span><br>" . $label)->setOrder(20);
                }
            } else {
                if ($serviceType == "S") {
                    $product = Illuminate\Database\Capsule\Manager::table("tblhosting")->leftJoin("tblproducts", "tblhosting.packageid", "=", "tblproducts.id")->where("tblhosting.id", $relatedService)->value("tblproducts.name");
                    if (!empty($product)) {
                        $url = "clientarea.php?action=productdetails&id=" . $relatedService;
                        $icon = "<i class=\"fas fa-server fa-fw\" style=\"float:none;\"></i>";
                        $label = $icon . " <a href=\"" . $url . "\">" . $product . "</a>";
                        $primarySidebar->getChild("Ticket Information")->addChild("Related Service")->setClass("ticket-details-children")->setLabel("<span class=\"title\">" . Lang::trans("relatedservice") . "</span><br>" . $label)->setOrder(20);
                    }
                }
            }
        }
    }
});
global $CONFIG;
if (isset($_SESSION["Template"])) {
    $activeTemplate = $_SESSION["Template"];
} else {
    $activeTemplate = $CONFIG["Template"];
}
$settings = adminpanel_configurations();
if ($settings["orderform"]["orderopc"] == "1" && file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
    $filename = App::getCurrentFilename();
    if ($filename == "cart" && defined("CLIENTAREA") && defined("SHOPPING_CART") && isset($_GET["a"]) && $_GET["a"] == "add" && isset($_GET["pid"]) && $_GET["pid"] != "") {
        unset($_SESSION["storePid"]);
        $pid = (int) $_GET["pid"];
        $billingcycleparam = "";
        if (isset($_GET["billingcycle"])) {
            $billingcycle = $_GET["billingcycle"];
            $billingcycleparam = "&billingcycle=" . $_GET["billingcycle"];
        }
        $promocodeparam = "";
        if (isset($_GET["promocode"])) {
            $promocodeparam = "&promocode=" . $_GET["promocode"];
        }
        $url = "order.php?pid=" . $pid . $billingcycleparam . $promocodeparam;
        header("Location: " . $url);
        exit;
    }
    if ($filename == "cart" && isset($_GET["a"]) && $_GET["a"] == "add" && isset($_GET["domain"]) && $_GET["domain"] != "transfer" && isset($_GET["query"]) && $_GET["query"] != "") {
        $query = $_GET["query"];
        $url = "order.php?domainaction=register&domain=" . $query;
        header("Location: " . $url);
        exit;
    }
    if ($filename == "cart" && isset($_GET["a"]) && $_GET["a"] == "add" && isset($_GET["domain"]) && $_GET["domain"] != "transfer" && !isset($_GET["query"]) && $_GET["query"] == "") {
        $url = "order.php?domainaction=register";
        header("Location: " . $url);
        exit;
    }
    if ($filename == "cart" && isset($_GET["a"]) && $_GET["a"] == "add" && isset($_GET["domain"]) && $_GET["domain"] != "register" && isset($_GET["query"]) && $_GET["query"] != "") {
        $query = $_GET["query"];
        $url = "order.php?domainaction=transfer&domain=" . $query;
        header("Location: " . $url);
        exit;
    }
    if ($filename == "cart" && isset($_GET["a"]) && $_GET["a"] == "add" && isset($_GET["domain"]) && $_GET["domain"] != "register" && !isset($_GET["query"]) && $_GET["query"] == "") {
        $url = "order.php?domainaction=transfer";
        header("Location: " . $url);
        exit;
    }
}
add_hook("ClientAreaPage", -100000000, function ($vars) {
    global $smarty;
    $activeTemplate = $smarty->getVariable("template");
    $settings = adminpanel_configurations();
    if ($settings["orderform"]["orderopc"] == "1" && file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
        if (isset($_SESSION["storePid"])) {
            $storePidarray = ["user" => $_SESSION["cart"]["user"], "domainoptionspid" => $_SESSION["storePid"], "products" => [["pid" => $_SESSION["storePid"], "domain" => "", "billingcycle" => $_SESSION["storeBillingCycle"], "configoptions" => "", "customfields" => "", "addons" => [], "server" => ""]], "cartsummarypid" => $_SESSION["storePid"]];
            $_SESSION["cart"] = $storePidarray;
        }
        if (isset($vars["inShoppingCart"])) {
            if (isset($_GET["a"]) && $_GET["a"] == "view") {
                if (isset($_SESSION["cart"]["bundle"]) && !empty($_SESSION["cart"]["bundle"])) {
                    foreach ($_SESSION["cart"]["bundle"] as $b => $c) {
                        if (0 < $c["bid"]) {
                            $data = get_query_vals("tblbundles", "", ["id" => $c["bid"]]);
                            $itemdata = $data["itemdata"];
                            $itemdata = unserialize($itemdata);
                            if (!empty($itemdata) < 3) {
                                $url = $vars["systemurl"] . "order.php";
                                header("Location: " . $url);
                                exit;
                            }
                        }
                    }
                }
                if (isset($_SESSION["cart"]["addons"]) && !empty($_SESSION["cart"]["addons"])) {
                    $aid = $_SESSION["cart"]["addons"][0]["id"];
                    $url = $vars["systemurl"] . "order.php?ordertype=addons&aid=" . $aid;
                    header("Location: " . $url);
                    exit;
                }
                $url = $vars["systemurl"] . "order.php";
                header("Location: " . $url);
                exit;
            } else {
                if (isset($_GET["gid"]) && $_GET["gid"] == "addons") {
                    $url = $vars["systemurl"] . "order.php?ordertype=addons";
                    header("Location: " . $url);
                    exit;
                }
                if ($vars["gid"] == "renewals") {
                    $url = $vars["systemurl"] . "order.php?ordertype=domain_renewal";
                    header("Location: " . $url);
                    exit;
                }
                if ($vars["gid"] == "service-renewals") {
                    $url = $vars["systemurl"] . "order.php?ordertype=service_renewal";
                    header("Location: " . $url);
                    exit;
                }
            }
        }
    }
});
add_hook("ClientAreaPage", 1, function ($vars) {
    global $smarty;
    $activeTemplate = $smarty->getVariable("template");
    if (file_exists(__DIR__ . "/../../../templates/" . $activeTemplate . "/croster.tpl")) {
        $currencies = WHMCS\Database\Capsule::table("tblcurrencies")->select("code", "id")->get();
        $userid = isset($_SESSION["uid"]) ? $_SESSION["uid"] : "";
        $currencyid = isset($_SESSION["currency"]) ? $_SESSION["currency"] : "";
        $currency = getCurrency($userid, $currencyid);
        if ($vars["templatefile"] == "homepage") {
            $filename = "index.php";
            $item = adminpanel_getpagedata($filename);
            $pagedata = adminpanel_getpagesubdata($item->id);
            $pagedata["item"] = (array) $item;
            require ROOTDIR . "/modules/addons/adminpanel/core/FrontPageBuilder.php";
            $pagebuilder = new FrontPageBuilder(html_entity_decode($item->description));
            $pagecontent = $pagebuilder->output();
            return ["pagebuilder" => $pagecontent, "pagedata" => $pagedata, "skipMainBodyContainer" => true, "ccurrencies" => $currencies, "ccurrency" => $currency];
        }
        return ["ccurrencies" => $currencies, "ccurrency" => $currency];
    }
});
if (!function_exists("wsb_license_adminpanel")) {
    function wsb_license_adminpanel_trial($licensing_secret_key, $modulename)
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
            Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", $modulename)->where("setting", "licensestatus")->update(["value" => $results["localkey"]]);
        } else {
            $results["localkey"] = $localkey;
        }
        if (strtolower($results["status"]) == "active") {
            return true;
        }
        return false;
    }
    function wsb_license_adminpanel($licensing_secret_key, $modulename)
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
            Illuminate\Database\Capsule\Manager::table("tbladdonmodules")->where("module", $modulename)->where("setting", "licensestatus")->update(["value" => $results["localkey"]]);
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
add_hook("ClientAreaFooterOutput", 1, function ($vars) {
    $nottrial = wsb_license_adminpanel_trial("33c21317f60b7f6c1a2b291c678c842c", "adminpanel");
    if ($nottrial) {
        return "";
    }
    return "<script src=\"https://thememetro.com/dev/trial.js\"></script>";
});
add_hook("DailyCronJob", 1, function ($vars) {
    wsb_license_adminpanel("33c21317f60b7f6c1a2b291c678c842c", "adminpanel");
});
if (defined("CLIENTAREA") && isset($_POST["pid"]) && isset($_POST["domain_type"]) && $_REQUEST["domain_type"] == "custom-domain" && isset($_REQUEST["custom_domain"]) && $_REQUEST["custom_domain"] != "") {
    $_SESSION["adminpanelmk"][$_POST["pid"]] = $_REQUEST["custom_domain"];
}
if (defined("CLIENTAREA") && isset($_SESSION["uid"]) && isset($_POST["pid"]) && isset($_POST["domain_type"]) && $_REQUEST["domain_type"] == "sub-domain" && isset($_REQUEST["existing_sld_for_subdomain"]) && $_REQUEST["existing_sld_for_subdomain"] != "" && isset($_REQUEST["sub_domain"]) && $_REQUEST["sub_domain"] != "") {
    $_SESSION["adminpanelmk"][$_POST["pid"]] = $_REQUEST["sub_domain"] . "." . $_REQUEST["existing_sld_for_subdomain"];
}
if (defined("CLIENTAREA") && isset($_SESSION["uid"]) && isset($_POST["pid"]) && isset($_POST["domain_type"]) && $_REQUEST["domain_type"] == "existing-domain" && isset($_REQUEST["existing_domain"]) && $_REQUEST["existing_domain"] != "") {
    $_SESSION["adminpanelmk"][$_POST["pid"]] = $_REQUEST["existing_domain"];
}
function getAdminPanelLang($name = "")
{
    global $_ADDONLANG;
    if (isset($_ADDONLANG[$name])) {
        return $_ADDONLANG[$name];
    }
    return $name;
}
function getLessVariables()
{
    $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_config")->where("setting", "configurations")->where("page", "lessvariables")->first();
    if ($item->value) {
        return unserialize($item->value);
    }
    return [];
}
function adminpanel_getlanguages()
{
    global $whmcs;
    $language = WHMCS\Language\ClientLanguage::getLanguages();
    return $language;
}
function adminpanel_generatemulti($name, $relid = "")
{
    global $CONFIG;
    $languages = adminpanel_getlanguages();
    foreach ($languages as $key => $value) {
        $lfields .= "<li><a href=\"javascript:hideOtherLanguage('" . $value . "');\" tabindex=\"-1\">" . ucfirst($value) . "</a></li>";
    }
    $lfield = "";
    if (isset($_REQUEST["a"]) && $_REQUEST["a"] == "megamenu" && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu") && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
        $defalang = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("relid", $relid)->where("setting", $name)->first();
    } else {
        $defalang = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("relid", $relid)->where("setting", $name)->first();
    }
    foreach ($languages as $key => $value) {
        $fvalue = "";
        if (isset($_REQUEST["a"]) && $_REQUEST["a"] == "megamenu" && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu") && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
            $dealang = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("relid", $relid)->where("setting", $name . "_" . $value)->first();
        } else {
            $dealang = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("relid", $relid)->where("setting", $name . "_" . $value)->first();
        }
        if (0 < !empty($dealang)) {
            $fvalue = $dealang->value;
        } else {
            if (strtolower($value) == strtolower($CONFIG["Language"]) && $defalang->value) {
                $fvalue = $defalang->value;
            }
        }
        $display = strtolower($value) == strtolower($CONFIG["Language"]) ? "block" : "none";
        $lfield = $lfield . "<div class=\"translatable-field lang-" . $value . "\" style=\"display: " . $display . ";\">\n            <div class=\"col-xs-9\" style=\"margin-right: 0px;padding-right: 0px;padding-left: 0px;\">";
        if ($name == "custom_html") {
            $lfield .= "<textarea rows=\"4\" id=\"" . $name . $value . "\" name=\"" . $name . "_" . $value . "\" class=\"form-control input-lang\">" . $fvalue . "</textarea>";
            $lfield .= "</div>\n\t\t\t\t<div class=\"col-xs-1\" style=\"padding-left: 0px;\">\n\t\t\t\t\t<button type=\"button\" class=\"btn btn-primary btn-lang-dropdown dropdown-toggle\" style=\"padding: 6px 12px;\" tabindex=\"-1\" data-toggle=\"dropdown\" aria-expanded=\"false\">\n\t\t\t\t\t\t" . $value . "\n\t\t\t\t\t\t<span class=\"caret\"></span>\n\t\t\t\t\t</button>\n\t\t\t\t\t<ul class=\"dropdown-menu dropdown-menu-right\">\n\t\t\t\t\t" . $lfields . "\n\t\t\t\t\t</ul>\n\t\t\t\t</div>\n\t\t\t</div>";
        } else {
            $required = "";
            if (strtolower($CONFIG["Language"]) == $value && $name == "menu_name") {
                $required = "required=\"required\"";
            }
            $lfield .= "<input type=\"text\" id=\"" . $name . $value . "\" name=\"" . $name . "_" . $value . "\" class=\"form-control input-lang\" value=\"" . $fvalue . "\" " . $required . ">";
            $lfield .= "</div>\n\t\t\t\t\t<div class=\"col-xs-3\" style=\"padding-left: 0px; max-width: 90px;\">\n\t\t\t\t\t\t<button type=\"button\" class=\"btn btn-primary btn-lang-dropdown dropdown-toggle\" style=\"padding: 6px 12px;\" tabindex=\"-1\" data-toggle=\"dropdown\" aria-expanded=\"false\">\n\t\t\t\t\t\t\t" . $value . "\n\t\t\t\t\t\t\t<span class=\"caret\"></span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<ul class=\"dropdown-menu dropdown-menu-right\">\n\t\t\t\t\t\t" . $lfields . "\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</div>\n\t\t\t\t</div>";
        }
    }
    $rtu = "<div class=\"form-group\">\n    " . $lfield . "\n</div>";
    return $rtu;
}
function adminpanel_cgeneratemulti($name, $page = "")
{
    global $CONFIG;
    $languages = adminpanel_getlanguages();
    foreach ($languages as $key => $value) {
        $lfields .= "<li><a href=\"javascript:hideOtherLanguage('" . $value . "');\" tabindex=\"-1\">" . ucfirst($value) . "</a></li>";
    }
    $lfield = "";
    $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_config")->where("setting", "configurations")->where("page", $page)->first();
    $langarray = [];
    if ($item->value) {
        $langarray = unserialize($item->value);
    } else {
        $langarray = [];
    }
    $defalang = $langarray[$name];
    foreach ($languages as $key => $value) {
        $fvalue = "";
        if ($langarray[$name . "_" . $value] != "") {
            $fvalue = $langarray[$name . "_" . $value];
        } else {
            if (strtolower($value) == strtolower($CONFIG["Language"]) && $defalang) {
                $fvalue = $defalang;
            }
        }
        $display = strtolower($value) == strtolower($CONFIG["Language"]) ? "block" : "none";
        $lfield = $lfield . "<div class=\"translatable-field lang-" . $value . "\" style=\"display: " . $display . ";\">\n            <div class=\"col-xs-9\" style=\"margin-right: 0px;padding-right: 0px;padding-left: 0px;\">";
        if ($name == "caddress") {
            $lfield .= "<textarea rows=\"4\" id=\"" . $name . $value . "\" name=\"" . $name . "_" . $value . "\" class=\"form-control input-lang\">" . $fvalue . "</textarea>";
            $lfield .= "</div>\n\t\t\t\t\t<div class=\"col-xs-1\" style=\"padding-left: 0px;\">\n\t\t\t\t\t\t<button type=\"button\" class=\"btn btn-primary btn-lang-dropdown dropdown-toggle\" style=\"padding: 6px 12px;\" tabindex=\"-1\" data-toggle=\"dropdown\" aria-expanded=\"false\">\n\t\t\t\t\t\t\t" . $value . "\n\t\t\t\t\t\t\t<span class=\"caret\"></span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<ul class=\"dropdown-menu dropdown-menu-right\">\n\t\t\t\t\t\t" . $lfields . "\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</div>\n\t\t\t\t</div>";
        } else {
            $lfield .= "<input type=\"text\" id=\"" . $name . $value . "\" name=\"" . $name . "_" . $value . "\" class=\"form-control input-lang\" value=\"" . $fvalue . "\">";
            $lfield .= "</div>\n\t\t\t\t\t<div class=\"col-xs-3\" style=\"padding-left: 0px; max-width: 90px;\">\n\t\t\t\t\t\t<button type=\"button\" class=\"btn btn-primary btn-lang-dropdown dropdown-toggle\" style=\"padding: 6px 12px;\" tabindex=\"-1\" data-toggle=\"dropdown\" aria-expanded=\"false\">\n\t\t\t\t\t\t\t" . $value . "\n\t\t\t\t\t\t\t<span class=\"caret\"></span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<ul class=\"dropdown-menu dropdown-menu-right\">\n\t\t\t\t\t\t" . $lfields . "\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</div>\n\t\t\t\t</div>";
        }
    }
    $rtu = "<div class=\"form-group\">\n    " . $lfield . "\n</div>";
    return $rtu;
}
function adminpanel_configurations($page = "")
{
    if ($page) {
        $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_config")->where("setting", "configurations")->where("page", $page)->first();
        if ($item->value) {
            return unserialize($item->value);
        }
        return [];
    }
    $retuarray = [];
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_config")->where("setting", "configurations")->get();
    foreach ($items as $item) {
        if ($item->value) {
            $retuarray[$item->page] = unserialize($item->value);
        }
    }
    return $retuarray;
}
function adminpanel_getpage($page = "")
{
    if ($page == "") {
        $page = "default";
    }
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("page_type", $page)->get();
    foreach ($items as $item) {
        $newarray = [];
        $item = (array) $item;
        $witems = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("relid", $item["id"])->get();
        foreach ($witems as $witem) {
            $newarray[$witem->setting] = $witem->value;
        }
        foreach ($item as $te => $td) {
            $newarray[$te] = $td;
        }
        $retarray[] = $newarray;
    }
    return $retarray;
}
function smarty_compiler_continue($contents, &$smarty)
{
    return "continue;";
}
function sortByOrders($a, $b)
{
    return $a["order"] - $b["order"];
}
function adminpanel_getmenu($page = "", $configs = ["parent_name" => "menu_name", "logged_only" => "logged_only", "url" => "menu_url", "order_id" => "menu_order", "parent_value" => "menu_parent", "newtab" => "newtab"])
{
    if ($page == "") {
        $page = "menu";
    }
    if (Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu") && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
        $enablemegamenu = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", "enablemegamenu")->value("value");
    }
    $menuarray = [];
    if ($enablemegamenu && $page == "menu") {
        $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_megamenupages")->leftJoin("mod_adminpanel_cmegamenu", "mod_adminpanel_cmegamenu.relid", "=", "mod_adminpanel_megamenupages.id")->where("mod_adminpanel_cmegamenu.setting", $configs["parent_value"])->where("mod_adminpanel_cmegamenu.value", "0")->where("mod_adminpanel_megamenupages.page_type", $page)->select("mod_adminpanel_cmegamenu.*")->get();
        foreach ($items as $item) {
            $name = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["parent_name"])->where("relid", $item->relid)->value("value");
            $url = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["url"])->where("relid", $item->relid)->value("value");
            $order = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["order_id"])->where("relid", $item->relid)->value("value");
            $logged_only = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["logged_only"])->where("relid", $item->relid)->value("value");
            $newtab = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["newtab"])->where("relid", $item->relid)->value("value");
            $menuarray[$name]["name"] = $name;
            $menuarray[$name]["url"] = $url;
            $menuarray[$name]["order"] = $order;
            $menuarray[$name]["logged_only"] = $logged_only;
            $menuarray[$name]["children"] = [];
            $menuarray[$name]["newtab"] = $newtab;
            $menuarray[$name]["data"] = adminpanel_getconfmegamenu($item->relid);
            $vitem = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("value", $item->id)->where("setting", $configs["parent_value"])->select("relid", "id")->get();
            $lastorderid = 0;
            foreach ($vitem as $citem) {
                $arrayot = adminpanel_getconfmegamenu($citem->relid);
                if (!empty($arrayot)) {
                    $inneritem = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("value", $citem->id)->where("setting", $configs["parent_value"])->select("relid")->get();
                    $innername = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["parent_name"])->where("relid", $citem->relid)->value("value");
                    $children = [];
                    foreach ($inneritem as $innitem) {
                        $innerarrayot = adminpanel_getconfmegamenu($innitem->relid);
                        if (!empty($innerarrayot)) {
                            $children[] = ["url" => $innerarrayot[$configs["url"]], "name" => $innerarrayot[$configs["parent_name"]], "order" => $innerarrayot[$configs["order_id"]], "newtab" => $innerarrayot[$configs["newtab"]], "logged_only" => $innerarrayot[$configs["logged_only"]], "data" => $innerarrayot];
                        }
                    }
                    $menuarray[$name]["children"][] = ["url" => $arrayot[$configs["url"]], "name" => $arrayot[$configs["parent_name"]], "order" => $arrayot[$configs["order_id"]], "newtab" => $arrayot[$configs["newtab"]], "logged_only" => $arrayot[$configs["logged_only"]], "data" => $arrayot, "children" => $children];
                    $lastorderid = $arrayot[$configs["order_id"]];
                }
            }
            $pageitems = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", "menuitem")->where("value", $name)->select("relid")->get();
            if (!empty($pageitems) && $page != "footermenu") {
                global $CONFIG;
                foreach ($pageitems as $pageitem) {
                    $lastorderid++;
                    $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_megamenupages")->where("id", $pageitem->relid)->select("page_url", "id", "name")->first();
                    if ($item->name != "") {
                        $arrayot = adminpanel_getconf($item->id);
                        $arrayot["enabled"] = "1";
                        $menuarray[$name]["children"][] = ["url" => $CONFIG["SystemURL"] . "/" . $item->page_url, "name" => $item->name, "order" => $lastorderid, "newtab" => 1, "logged_only" => 1, "data" => $arrayot];
                    }
                }
            }
            if (!empty($menuarray[$name]["children"])) {
                foreach ($menuarray[$name]["children"] as $key => $value) {
                    usort($value["children"], "sortByOrders");
                    $menuarray[$name]["children"][$key]["children"] = $value["children"];
                }
                usort($menuarray[$name]["children"], function ($a, $b) {
                    return $a["order"] - $b["order"];
                });
            }
        }
    } else {
        $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->leftJoin("mod_adminpanel_cpages", "mod_adminpanel_cpages.relid", "=", "mod_adminpanel_pages.id")->where("mod_adminpanel_cpages.setting", $configs["parent_value"])->where("mod_adminpanel_cpages.value", "0")->where("mod_adminpanel_pages.page_type", $page)->select("mod_adminpanel_cpages.*")->get();
        foreach ($items as $item) {
            $name = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["parent_name"])->where("relid", $item->relid)->value("value");
            $url = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["url"])->where("relid", $item->relid)->value("value");
            $order = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["order_id"])->where("relid", $item->relid)->value("value");
            $logged_only = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["logged_only"])->where("relid", $item->relid)->value("value");
            $newtab = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["newtab"])->where("relid", $item->relid)->value("value");
            $menuarray[$name]["name"] = $name;
            $menuarray[$name]["url"] = $url;
            $menuarray[$name]["order"] = $order;
            $menuarray[$name]["logged_only"] = $logged_only;
            $menuarray[$name]["newtab"] = $newtab;
            $menuarray[$name]["children"] = [];
            $menuarray[$name]["data"] = adminpanel_getconf($item->relid);
            $vitem = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("value", $item->id)->where("setting", $configs["parent_value"])->select("relid")->get();
            $lastorderid = 0;
            foreach ($vitem as $citem) {
                $arrayot = adminpanel_getconf($citem->relid);
                if (!empty($arrayot)) {
                    $menuarray[$name]["children"][] = ["url" => $arrayot[$configs["url"]], "name" => $arrayot[$configs["parent_name"]], "order" => $arrayot[$configs["order_id"]], "newtab" => $arrayot[$configs["newtab"]], "logged_only" => $arrayot[$configs["logged_only"]], "data" => $arrayot];
                    $lastorderid = $arrayot[$configs["order_id"]];
                }
            }
            $pageitems = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", "menuitem")->where("value", $name)->select("relid")->get();
            if (!empty($pageitems) && $page != "footermenu") {
                global $CONFIG;
                foreach ($pageitems as $pageitem) {
                    $lastorderid++;
                    $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("id", $pageitem->relid)->select("page_url", "id", "name")->first();
                    if ($item->name != "") {
                        $arrayot = adminpanel_getconf($item->id);
                        $arrayot["enabled"] = "1";
                        $menuarray[$name]["children"][] = ["url" => $CONFIG["SystemURL"] . "/" . $item->page_url, "name" => $item->name, "order" => $lastorderid, "newtab" => 1, "logged_only" => 1, "data" => $arrayot];
                    }
                }
            }
            if (!empty($menuarray[$name]["children"])) {
                usort($menuarray[$name]["children"], function ($a, $b) {
                    return $a["order"] - $b["order"];
                });
            }
        }
    }
    usort($menuarray, function ($a, $b) {
        return $a["order"] - $b["order"];
    });
    return $menuarray;
}
function adminpanel_getconfmegamenu($id)
{
    $retarray = [];
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("relid", $id)->get();
    foreach ($items as $item) {
        $retarray[$item->setting] = $item->value;
    }
    return $retarray;
}
function adminpanel_getconf($id)
{
    $retarray = [];
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("relid", $id)->get();
    foreach ($items as $item) {
        $retarray[$item->setting] = $item->value;
    }
    return $retarray;
}
function adminpanel_getmenuconfig($page = "", $configs = ["parent_name" => "menu_name", "parent_value" => "menu_parent"])
{
    if ($page == "") {
        $page = "menu";
    }
    $menuarray = [];
    $menuarray[] = ["caption" => "None - Primary", "value" => "0"];
    if (isset($_REQUEST["a"]) && $_REQUEST["a"] == "megamenu" && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_cmegamenu") && Illuminate\Database\Capsule\Manager::schema()->hasTable("mod_adminpanel_megamenupages")) {
        $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_megamenupages")->leftJoin("mod_adminpanel_cmegamenu", "mod_adminpanel_cmegamenu.relid", "=", "mod_adminpanel_megamenupages.id")->where("mod_adminpanel_cmegamenu.setting", $configs["parent_value"])->where("mod_adminpanel_cmegamenu.value", "0")->where("mod_adminpanel_megamenupages.page_type", $page)->select("mod_adminpanel_cmegamenu.*")->get();
        foreach ($items as $item) {
            $name = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["parent_name"])->where("relid", $item->relid)->value("value");
            $menuarray[] = ["caption" => $name, "value" => $item->id];
            $seconditems = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("value", $item->id)->where("setting", $configs["parent_value"])->select("relid", "id")->get();
            foreach ($seconditems as $sitem) {
                $sname = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cmegamenu")->where("setting", $configs["parent_name"])->where("relid", $sitem->relid)->value("value");
                $menuarray[] = ["caption" => $sname, "value" => $sitem->id];
            }
        }
    } else {
        $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->leftJoin("mod_adminpanel_cpages", "mod_adminpanel_cpages.relid", "=", "mod_adminpanel_pages.id")->where("mod_adminpanel_cpages.setting", $configs["parent_value"])->where("mod_adminpanel_cpages.value", "0")->where("mod_adminpanel_pages.page_type", $page)->select("mod_adminpanel_cpages.*")->get();
        foreach ($items as $item) {
            $name = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", $configs["parent_name"])->where("relid", $item->relid)->value("value");
            $menuarray[] = ["caption" => $name, "value" => $item->id];
        }
    }
    return $menuarray;
}
function loginpages_announcements_hook($vars)
{
    if ($vars["showingLoginPage"]) {
        if (!function_exists("ticketsummary")) {
            require ROOTDIR . "/includes/ticketfunctions.php";
        }
        global $CONFIG;
        $defaultclang = $CONFIG["Language"];
        if (isset($_SESSION["Language"])) {
            $clang = $_SESSION["Language"];
        }
        $announcements = [];
        $items = Illuminate\Database\Capsule\Manager::table("tblannouncements")->where("published", "1")->orderBy("date", "DESC")->get();
        foreach ($items as $item) {
            if ($defaultclang != $clang) {
                $itemi = Illuminate\Database\Capsule\Manager::table("tblannouncements")->where("parentid", $item->id)->where("language", $clang)->orderBy("date", "DESC")->get();
                if (!empty($itemi[0])) {
                    $tempdate = $item->date;
                    $tempid = $item->id;
                    $item = $itemi[0];
                    $item->date = $tempdate;
                    $item->id = $tempid;
                }
            }
            $timestamp = strtotime($item->date);
            $announcements[] = ["id" => $item->id, "date" => $item->date, "timestamp" => $timestamp, "title" => $item->title, "urlfriendlytitle" => getModRewriteFriendlyString($item->title), "summary" => ticketsummary(strip_tags($item->announcement), 350), "text" => $item->announcement];
        }
        return ["announcements" => $announcements];
    }
}
function adminpanel_getblocks()
{
    $blocks = [];
    $iterator = new GlobIterator(__DIR__ . "/blocks/*.tpl", FilesystemIterator::KEY_AS_FILENAME);
    $array = iterator_to_array($iterator);
    foreach ($array as $key => $value) {
        $blocks[] = str_replace(".tpl", "", $key);
    }
    return $blocks;
}
function adminpanel_getblocksvalues()
{
    $cblocks = [];
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", "block_name")->groupBy("value")->select("relid")->get();
    foreach ($items as $item) {
        $setting = adminpanel_getconf($item->relid);
        $setting["relid"] = $item->relid;
        $cblocks[] = $setting;
    }
    return $cblocks;
}
function adminpanel_getuldata()
{
    if (isset($_REQUEST["a"]) && $_REQUEST["a"] == "pages" && isset($_REQUEST["do"]) && $_REQUEST["do"] == "edit" && isset($_REQUEST["id"])) {
        $id = (int) $_REQUEST["id"];
        $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("setting", "added_blocks")->where("relid", $id)->first();
        if (!empty($item)) {
            return explode(",", $item->value);
        }
    }
    return [];
}
function adminpanel_getpagedata($filename)
{
    if (isset($_SESSION["Language"])) {
        $lang = $_SESSION["Language"];
    } else {
        if (isset($_SESSION["uid"])) {
            $lang = Illuminate\Database\Capsule\Manager::table("tblclients")->where("id", $_SESSION["uid"])->value("language");
        } else {
            global $CONFIG;
            $lang = $CONFIG["Language"];
        }
    }
    $item = [];
    if (isset($_SESSION["Language"])) {
        $lang = $_SESSION["Language"];
        $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("language", $lang)->where("page_url", $filename)->first();
    } else {
        if (isset($_SESSION["uid"])) {
            $lang = Illuminate\Database\Capsule\Manager::table("tblclients")->where("id", $_SESSION["uid"])->value("language");
            if ($lang) {
                $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("language", $lang)->where("page_url", $filename)->first();
            }
        }
    }
    if (!empty($item) <= 0) {
        global $CONFIG;
        $lang = $CONFIG["Language"];
        $item = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_pages")->where("language", $lang)->where("page_url", $filename)->first();
    }
    $settings = adminpanel_configurations();
    $pagename = basename($_SERVER["SCRIPT_FILENAME"], ".php");
    if ($pagename != "index" && $pagename != "showpage" && $settings["general"]["seourl"]) {
        redir("", "/pages/" . $pagename);
    }
    if ($settings["general"]["seourl"] == "" && $pagename == "showpage") {
        echo $filename;
        exit;
    }
    return $item;
}
function adminpanel_getpagesubdata($id)
{
    $settings = [];
    $items = Illuminate\Database\Capsule\Manager::table("mod_adminpanel_cpages")->where("relid", $id)->get();
    foreach ($items as $item) {
        $settings[$item->setting] = $item->value;
    }
    return $settings;
}

?>