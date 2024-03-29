<?php require_once('get-common/Utilities.php') ?>
<?php require_once('get-common/keys.php') ?>
<?php require_once('get-common/eBaySession.php') ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<TITLE>Revise Item's Price</TITLE>
</HEAD>
<BODY>
<FORM action="ebay_ReviseItem-Price.php" method="post">
<TABLE cellpadding="2" border="0">

    <TR>
		<TD>Item No:</TD>
		<TD><INPUT type="text" name="itemNo"  size=30></TD>
	</TR>
	    <TR>
		<TD>RemoveFields:</TD>
		<TD>ListingEnhancement[BoldTitle]</TD>
	</TR>
       <TR>
		<TD>Modifed startPrice</TD>
		<TD><INPUT type="text" name="startPrice" value="<?php echo rand(1,200) / 100 ?>"></TD>
	</TR>
    <TR>
		<TD>Modified buyItNowPrice</TD>
		<TD><INPUT type="text" name="buyItNowPrice" value="<?php echo rand(299,599) / 100; ?>"> (set to 0.0 for Store)</TD>
	</TR>
	<TR>
		<TD colspan="2" align="right"><INPUT type="submit" name="submit" value="Revise Price And Remove  BoldTitle"></TD>
	</TR>
</TABLE>
</FORM>


<?php
if(isset($_POST['itemNo']))
{
	ini_set('magic_quotes_gpc', false);    // magic quotes will only confuse things like escaping apostrophe
	//Get the item entered
	$itemNo     = $_POST['itemNo'];
	$startPrice     = $_POST['startPrice'];
	$buyItNowPrice     = $_POST['buyItNowPrice'];
	
	
	
	//SiteID must also be set in the Request's XML
	//SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
	//SiteID Indicates the eBay site to associate the call with
	$siteID = 0;
	//the call being made:
	$verb = 'ReviseItem';
	$detailLevel ='ReturnAll';
	$errorLanguage ='en_US';

	$site="US";
	$currency="USD";
	$country ="US";


	///Build the request Xml string
	$requestXmlBody  = '<?xml version="1.0" encoding="utf-8" ?>';
	$requestXmlBody .= '<ReviseItemRequest   xmlns="urn:ebay:apis:eBLBaseComponents">';
	$requestXmlBody .= "<RequesterCredentials><eBayAuthToken>$userToken</eBayAuthToken></RequesterCredentials>";
	$requestXmlBody .= "<DetailLevel>$detailLevel</DetailLevel>";
	$requestXmlBody .= "<ErrorLanguage>$errorLanguage</ErrorLanguage>";
	$requestXmlBody .= "<Version>$compatabilityLevel</Version>";
	
	$requestXmlBody .= "<Item>";
   	$requestXmlBody .= " <ItemID>$itemNo</ItemID>";
	$requestXmlBody .= "<StartPrice>$startPrice</StartPrice>";
	$requestXmlBody .= "<BuyItNowPrice currencyID=\"$currency\">$buyItNowPrice</BuyItNowPrice>";
 	$requestXmlBody .= " </Item>";
	$requestXmlBody .= " <DeletedField>Item.ListingEnhancement[BoldTitle]</DeletedField>";
	$requestXmlBody .= '</ReviseItemRequest  >';
	
	//Create a new eBay session with all details pulled in from included keys.php
	$session = new eBaySession($userToken, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, $verb);
	
	//send the request and get response
	$responseXml = $session->sendHttpRequest($requestXmlBody);
	if(stristr($responseXml, 'HTTP 404') || $responseXml == '')
		die('<P>Error sending request');
	
	//Xml string is parsed and creates a DOM Document object
	$responseDoc = new DomDocument();
	$responseDoc->loadXML($responseXml);
	
	//get any error nodes
	$errors = $responseDoc->getElementsByTagName('Errors');
	
	//if there are error nodes
	if($errors->length > 0)
	{
		echo '<P><B>eBay returned the following error(s):</B>';
		//display each error
		//Get error code, ShortMesaage and LongMessage
		$code     = $errors->item(0)->getElementsByTagName('ErrorCode');
		$shortMsg = $errors->item(0)->getElementsByTagName('ShortMessage');
		$longMsg  = $errors->item(0)->getElementsByTagName('LongMessage');
		//Display code and shortmessage
		echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
		//if there is a long message (ie ErrorLevel=1), display it
		if(count($longMsg) > 0)
			echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));
		
	} else { //no errors
		
		
		//get results nodes
		$responses = $responseDoc->getElementsByTagName("ReviseItemResponse");
		foreach ($responses as $response) {
			$acks = $response->getElementsByTagName("Ack");
			$ack   = $acks->item(0)->nodeValue;
			echo "Ack = $ack <BR />\n";   // Success if successful
			
			$endTimes  = $response->getElementsByTagName("EndTime");
			$endTime   = $endTimes->item(0)->nodeValue;
			echo "endTime = $endTime <BR />\n";
			
			$itemIDs  = $response->getElementsByTagName("ItemID");
			$itemID   = $itemIDs->item(0)->nodeValue;
			echo "itemID = $itemID <BR />\n";
			
			$linkBase = "http://cgi.sandbox.ebay.com/ws/eBayISAPI.dll?ViewItem&item=";
			echo "<a href=$linkBase" . $itemID . ">$title</a> <BR />";
			
			$feeNodes = $responseDoc->getElementsByTagName('Fee');
			foreach($feeNodes as $feeNode) {
				$feeNames = $feeNode->getElementsByTagName("Name");
				if ($feeNames->item(0)) {
					$feeName = $feeNames->item(0)->nodeValue;
					$fees = $feeNode->getElementsByTagName('Fee');  // get Fee amount nested in Fee
					$fee = $fees->item(0)->nodeValue;
					if ($fee > 0.0) {
						if ($feeName == 'ListingFee') {
							printf("<B>$feeName : %.2f </B><BR>\n", $fee); 
						} else {
							printf("$feeName : %.2f <BR>\n", $fee);
						}      
					}  // if $fee > 0
				} // if feeName
			} // foreach $feeNode
			
		} // foreach response
		

		
	} // if $errors->length > 0
}
?>

</BODY>
</HTML>
