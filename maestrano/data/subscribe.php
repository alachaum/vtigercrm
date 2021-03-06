<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

$maestrano = MaestranoService::getInstance();

if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $log = new MnoSoaBaseLogger();

    $notification = json_decode(file_get_contents('php://input'), false);
    $notification_entity = strtoupper(trim($notification->entity));
    
    $log->debug("Notification = ". json_encode($notification));
    
    switch ($notification_entity) {
	    case "COMPANY":
              if (class_exists('MnoSoaCompany')) {
                $mno_company = new MnoSoaCompany($opts['db_connection'], $log);
                $mno_company->receiveNotification($notification);
              }
      break;
      case "TAXCODES":
              if (class_exists('MnoSoaTax')) {
                $mno_tax = new MnoSoaTax($opts['db_connection'], $log);
                $mno_tax->receiveNotification($notification);
              }
      break;
      case "ORGANIZATIONS":
              if (class_exists('MnoSoaOrganization')) {
                $mno_org = new MnoSoaOrganization($opts['db_connection'], $log);		
                $mno_org->receiveNotification($notification);
              }
			break;
      case "PERSONS":
              if (class_exists('MnoSoaPerson')) {
                $mno_person = new MnoSoaPerson($opts['db_connection'], $log);   
                $mno_person->receiveNotification($notification);
              }
      break;
      case "ITEMS":
              if (class_exists('MnoSoaItem')) {
                $mno_item = new MnoSoaItem($opts['db_connection'], $log);   
                $mno_item->receiveNotification($notification);
              }
      break;
      case "INVOICES":
              if (class_exists('MnoSoaInvoice')) {
                $mno_invoice = new MnoSoaInvoice($opts['db_connection'], $log);   
                $mno_invoice->receiveNotification($notification);
              }
      break;
    }
}

?>
