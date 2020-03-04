<?php
// iOS Activation Server
$activation= (array_key_exists('activation-info-base64', $_POST) 
			  ? base64_decode($_POST['activation-info-base64']) 
			  : array_key_exists('activation-info', $_POST) ? $_POST['activation-info'] : '');
if(!isset($activation) || empty($activation)) { exit('make sure device is connected'); }
$encodedrequest = new DOMDocument;
$encodedrequest->loadXML($activation);
$activationDecoded= base64_decode($encodedrequest->getElementsByTagName('data')->item(0)->nodeValue);
$decodedrequest = new DOMDocument;
$decodedrequest->loadXML($activationDecoded);
$nodes = $decodedrequest->getElementsByTagName('dict')->item(0)->getElementsByTagName('*');
for ($i = 0; $i < $nodes->length - 1; $i=$i+2)
{
	switch ($nodes->item($i)->nodeValue)
	{
		case "ActivationRandomness": $activationRandomness = $nodes->item($i + 1)->nodeValue; break;
		case "DeviceCertRequest": $deviceCertRequest = base64_decode($nodes->item($i + 1)->nodeValue); break;
		case "DeviceClass": $deviceClass = $nodes->item($i + 1)->nodeValue; break;
		case "SerialNumber": $serialNumber = $nodes->item($i + 1)->nodeValue; break;
		case "UniqueDeviceID": $uniqueDeviceID = $nodes->item($i + 1)->nodeValue; break;
		case "MobileEquipmentIdentifier": $MobileEquipmentIdentifier = $nodes->item($i + 1)->nodeValue; break;
		case "InternationalMobileEquipmentIdentity": $imei = $nodes->item($i + 1)->nodeValue; break;
		case "InternationalMobileSubscriberIdentity": $imsi = $nodes->item($i + 1)->nodeValue; break;
		case "IntegratedCircuitCardIdentity": $iccid = $nodes->item($i + 1)->nodeValue; break;
		case "UniqueChipID": $ucid = $nodes->item($i + 1)->nodeValue; break;
		case "ProductType": $productType = $nodes->item($i + 1)->nodeValue; break;
		case "ActivationState": $activationState = $nodes->item($i + 1)->nodeValue; break;
		case "ProductVersion": $productVersion = $nodes->item($i + 1)->nodeValue; break;
	}
}
$wildcardTicket = file_get_contents('wildcardticket.txt');
$accountToken=
'{'.(isset($imei) ? "\n\t".'"InternationalMobileEquipmentIdentity" = "'.$imei.'";' : '').'
   '.(isset($meid) ? "\n\t".'"MobileEquipmentIdentifier" = "'.$meid.'";' : '').
    "\n\t".'"ActivityURL" = "https://albert.apple.com/deviceservices/activity";'.
    "\n\t".'"ActivationRandomness" = "'.$activationRandomness.'";'.
    "\n\t".'"UniqueDeviceID" = "'.$uniqueDeviceID.'";'.
    "\n\t".'"SerialNumber" = "'.$serialNumber.'";'.
    "\n\t".'"ProductType" = "'.$productType.'";'.
    "\n\t".'"CertificateURL" = "https://albert.apple.com/deviceservices/certifyMe";'.
    "\n\t".'"PhoneNumberNotificationURL" = "https://albert.apple.com/deviceservices/phoneHome";'.
	"\n\t".'"WildcardTicket" = "'.$wildcardTicket.'";'.
	"\n".
 '}';
$accountTokenBase64=base64_encode($accountToken);
$private = file_get_contents('signature.key');
$pkeyid = openssl_pkey_get_private($private);
openssl_sign($accountToken, $signature, $pkeyid);
openssl_free_key($pkeyid);
$accountTokenSignature= base64_encode($signature);
$accountTokenCertificateBase64 = file_get_contents('AccountTokenCertificate.crt');
$fairPlayKeyData = file_get_contents('FairPlayKeyData.pem');
$deviceCertificate = file_get_contents('deviceCertificate.crt');
$response ='<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="keywords" content="iTunes Store" /><meta name="description" content="iTunes Store" /><title>iPhone Activation</title><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/shared/common-min.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/deviceservices/stylesheets/styles.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/pages/IPAJingleEndPointErrorPage-min.css" charset="utf-8" rel="stylesheet" /><link href="resources/auth_styles.css" charset="utf-8" rel="stylesheet" /><script id="protocol" type="text/x-apple-plist">
<plist version="1.0">
	<dict>
		<key>'.($deviceClass == "iPhone" ? 'iphone' : 'device').'-activation</key>
		<dict>
			<key>activation-record</key>
			<dict>
				<key>FairPlayKeyData</key>
				<data>'.$fairPlayKeyData.'</data>
				<key>AccountTokenCertificate</key>
				<data>'.$accountTokenCertificateBase64.'</data>
				<key>DeviceCertificate</key>
				<data>'.$deviceCertificate.'</data>
				<key>AccountTokenSignature</key>
				<data>'.$accountTokenSignature.'</data>
				<key>AccountToken</key>
				<data>'.$accountTokenBase64.'</data>
			</dict>
			<key>unbrick</key>
			<true/>
			<key>show-settings</key>
			<true/>
		</dict>
	</dict>
</plist>
</head>
</html>';
echo $response;
exit;
?>