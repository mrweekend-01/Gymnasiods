<?php
	@require_once("../common/ipenv.php");
	@require_once("../common/com.php");
	$REALTIME_STOCK	= false;	
	$BACKUP_ORDER	= true;		
	$MERCHANT_TO	= "<>";		
	$MERCHANT_FROM	= "<>";	
	$MERCHANT_BCC 	= true;		
	$MERCHANT_DATE_FORMAT = "dd/mm/yyyy";	
	$CRLF 			= "\r\n";				
	$HTTP_PREFIX	= (false)?'https://':'http://';		
	$VO_CTR = "1e445f20ef9f42609ea4c36d4ac8410c";					
	$MAX_PERIOD = -1;				
	$ORDER_EMAIL_ADD_URL = false;		
	$hostsite = $_SERVER['HTTP_HOST'];
	$c_subject = isset( $_REQUEST['client_subject'] ) ? $_REQUEST['client_subject'] : "";
	$c_subject = str_replace( '{SiteUrl}', $hostsite, stripslashes( $c_subject ) );
	$v_subject = isset( $_REQUEST['vendor_subject'] ) ? $_REQUEST['vendor_subject'] : "";	
	$v_subject = str_replace( '{SiteUrl}', $hostsite, stripslashes( $v_subject ) );	
	$cinst = isset( $_REQUEST['cinst'] ) ? stripslashes($_REQUEST['cinst']) : "";		
	$oanchor = isset( $_REQUEST['oanchor'] ) ? stripslashes($_REQUEST['oanchor']) : "";	
	$ohtml = isset( $_REQUEST['ohtml'] ) ? stripslashes( $_REQUEST['ohtml'] ) : "";		
	$otxt = isset( $_REQUEST['otxt'] ) ? stripslashes( $_REQUEST['otxt'] ) : "";		
	$otxt = str_replace( "<br/>", " ", $otxt );											
	$ojson = isset( $_REQUEST['ojson'] ) ? stripslashes( $_REQUEST['ojson'] ) : "";		
	$ostate = isset( $_REQUEST['ostate'] ) ? stripslashes( $_REQUEST['ostate'] ) : "";	
	$cemail = isset( $_REQUEST['cemail'] ) ? $_REQUEST['cemail'] : "";
	$ctr = isset( $_REQUEST['ctr'] ) ? $_REQUEST['ctr'] : "";
	$orderID = isset( $_REQUEST['oid'] ) ? $_REQUEST['oid'] : "";
	$result = '';
	$ohtml_url = "";
	$otxt_url = "";
	$otxt_server = "";	
	$odlfile_entry = "";	
	if( strstr($otxt, '[COMMANDE]') === false ) {
		$otxt_server = "[SERVER]\n";
		if( strstr($otxt, "_File = ") !== false )
			$odlfile_entry = "_File = ";
	} else {
		$otxt_server = "[SERVEUR]\n";		
		if( strstr($otxt, "_Fichier = ") !== false )
			$odlfile_entry = "_Fichier = ";	
	}
	$otxt_server .= "Client IP = " . PMA_getIp() . "\nDate = ";
	if( $MERCHANT_DATE_FORMAT == "dd/mm/yyyy" ) {
		$otxt_server .= date("d-m-Y @ H:i:s (\G\M\TO)") . "\n";
	} else
		$otxt_server .= date("m-d-Y @ H:i:s (\G\M\TO)") . "\n";
	if( $ctr == "" || $ctr != $VO_CTR )	
		die( 'ERR_CTR '  );
	if( $BACKUP_ORDER ) { 
		if( !is_dir( 'data' ) ) {
			mkdir( 'data', 0775 );
			file_put_contents( "./data/index.html", "<html></html>");
		}
		$BOM = "\xEF\xBB\xBF";
		$logfile = "data/$orderID-log.txt";
		$fp=fopen( $logfile, "w" );
		if( $fp !== false ) { 
			fwrite( $fp, "error=$orderID" );
			fclose( $fp );
		} else
			$logfile = "";
		checkIServDataDir( '../', 'twsc', false );
		if( $odlfile_entry != "" ) {
			$odl_url = $HTTP_PREFIX . $hostsite . dirname( $_SERVER['SCRIPT_NAME'] ) . "/dl.php?";
			$odl_url = str_replace( "/twsc/", "/dlfiles/", $odl_url ); 
			$inifile_content = "";
			$dlf_index = 1;
			while ( strstr($otxt, "\n".$dlf_index.$odlfile_entry) !== false ) {
				$dlfile = ExtractStringBetween( "\n".$dlf_index.$odlfile_entry, "\n", $otxt );
				$otxt = str_replace( $dlfile, $odl_url."dlfile=".$dlfile."&dlorder=".$orderID."&dlkey=".getCtr($orderID, $dlfile), $otxt );
				$inifile_content .= "downloadcount_".$dlfile."=0\r\nexpiredate_".$dlfile."=";
				$inifile_content .= ( ( $MAX_PERIOD <= 0 ) ? "0" : date( "Y-m-d", strtotime( "+".$MAX_PERIOD." days" ) ) );
				$inifile_content .= "\r\n";
				$dlf_index++;
			} 
			if( is_dir( "../dlfiles/data" ) ) {
				if( !$fh = fopen( "../dlfiles/data/".$orderID.".ini", "w+") ) {
				} else {
					fwrite( $fh, $inifile_content );
					fclose( $fh );
				}
			}
		}
		$fp=fopen( "data/$orderID.txt", "w" );
		if( $fp !== false ) { 
			if( fwrite($fp, $BOM . $otxt) === false )	
				$result .= 'ERR_BKTXT ';
			else {
				$otxt_url = $HTTP_PREFIX . $hostsite . dirname( $_SERVER['SCRIPT_NAME'] ) . "/so.php?oid=$orderID&fmt=txt&ctr=" . getCtr( $orderID, "txt" );
				$otxt_server .= "Text Order = $otxt_url\n";
			}
			fclose($fp);
			$fp=fopen( "data/$orderID.html", "w" );
			if( $fp !== false ) { 
				if( fwrite($fp, $ohtml) === false )
					$result .= 'ERR_BKHTM ';
				else {
					$ohtml_url = $HTTP_PREFIX . $hostsite . dirname( $_SERVER['SCRIPT_NAME'] ) . "/so.php?oid=$orderID&fmt=html&ctr=" . getCtr( $orderID, "html" );
					$otxt_server .= "HTML Order = $ohtml_url\n";
				}
				fclose($fp);
			}
			$fp=fopen( "data/$orderID.json", "w" );
			if( $fp !== false ) { 
				if( fwrite($fp, $BOM . $ojson) === false )	
					$result .= 'ERR_BKJSON ';
				fclose($fp);
			}
			$fp=fopen( "data/$orderID.state", "w" );
			if( $fp !== false ) { 
				if( fwrite($fp, $BOM . $ostate) === false )	
					$result .= 'ERR_BKSTATE ';
				fclose($fp);
			}
			if( strlen($logfile) > 0 )
				unlink($logfile);
		}
	}
	$otxt = "$otxt_server\n$otxt";
	if( $REALTIME_STOCK && $ojson !== '') {
		if( !is_dir( 'data' ) ) {
			mkdir( 'data', 0775 );
			file_put_contents( "./data/index.html", "<html></html>");
		}
		if( !function_exists('json_decode') ) {
			@require_once('../common/json.php');
			function json_decode($data) {
				$json = new Services_JSON();
				return( $json->decode($data) );
			}
		}
		if( !function_exists('json_encode') ) {
			@require_once('../common/json.php');
			function json_encode($data) {
				$json = new Services_JSON();
				return( $json->encode($data) );
			}
		}
		$json_realtime_stock = 'SC_REALTIME_STOCK={}';
		if(file_exists( "data/realtime.stock.js" )) {
			$json_realtime_stock = file_get_contents( "data/realtime.stock.js" );
			if ($json_realtime_stock === false)
				$json_realtime_stock = 'SC_REALTIME_STOCK={}';
		}
		$json_realtime_stock = (array)json_decode(strstr($json_realtime_stock, '{'));
		if($json_realtime_stock === false) {
			$json_realtime_stock = array();
		}
		$json_order = json_decode($ojson);
		if($json_order) {
			$items = $json_order->items;
			for( $i=0; $i < count( $items ); $i++ ) {
				$stock = $json_realtime_stock[ $items[$i]->twref ];
				if( gettype($stock) !== 'integer')
					$stock = $items[$i]->stock_rt;
				if( $stock > 0)
					$json_realtime_stock[ $items[$i]->twref ] = max(0, $stock - $items[$i]->qty_bought);
			}
		file_put_contents( "data/realtime.stock.js", "SC_REALTIME_STOCK = " . json_encode($json_realtime_stock));
		}
	}
	$to = "";
	$headers = 
		"MIME-Version: 1.0" . $CRLF .
		"Content-Type: text/plain; charset=utf-8" . $CRLF .
		"Content-Transfer-Encoding: 8bit" . $CRLF .	
		"From: $MERCHANT_FROM" . $CRLF .
		"Return-Path: $MERCHANT_FROM" . $CRLF .
		"X-Mailer: PHP/" . phpversion() . $CRLF;
	$to = $MERCHANT_TO;
	if( false === false )
		$result .= 'ERR_DEMO ';
	if( $cemail != "" ) {
		sleep(1);
		$to = "";
		$headers = 
			"MIME-Version: 1.0" . $CRLF .
			"Content-Type: text/html; charset=utf-8" . $CRLF .
			"Content-Transfer-Encoding: 8bit" . $CRLF;	
		$to = $cemail;
		if( $MERCHANT_BCC )
			$headers .= "Bcc: $MERCHANT_TO" . $CRLF;
		$headers .= 
			"From: $MERCHANT_FROM" . $CRLF .
			"Return-Path: $MERCHANT_FROM" . $CRLF .
			"X-Mailer: PHP/" . phpversion() . $CRLF;
		if( $ORDER_EMAIL_ADD_URL )
			$cinst .= "<a href=\"$ohtml_url\">$oanchor</a>";
		if( strpos( $cinst, "<html" ) === false )
			$cinst = "<html><head><meta http-equiv=\"content-type\" content=\"text/html;charset=UTF-8\"></head><body>$cinst</body></html>";
		if( $ohtml_url !== '' )
			$cinst = str_replace( '<!--EOID-->', '</a>', str_replace( '<!--SOID-->', "<a href=\"$ohtml_url\">", $cinst ) );
		if( false === false )
			$result .= ' ';
	}
	echo $result;
?>
