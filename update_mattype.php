<?php

	/*
		Gets the item from the Alma item GET API
	*/
	function getxml($url)
	{
		$curl = curl_init();
	        curl_setopt($curl,CURLOPT_URL, $url);
	        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
	        $result = curl_exec($curl);
	        curl_close($curl);
	        if(isset($result))
	        {
	        	// Check for limit error
			$xml = new SimpleXMLElement($result);
			if ($xml->errorsExist == "true" )
			{
				echo "Reached too many API calls: " . $xml->errorList->error->errorMessage;
				exit;
			}
			else
			{	
				return $xml;
	        	}
	        }
	}

	/*
		Call to the Alma PUT API to update the item material type
	*/
	function putxml($url,$body)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/xml"));
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);
		try 
		{
			$xml = new SimpleXMLElement($response);
			if ($xml->errorsExist == "true" )
			{
				echo "Reached too many API calls: " . $xml->errorList->error->errorMessage;
				exit;
			}
			else
			{
				return $xml;
        	}			
		}
		catch(Exception $exception)
		{
			echo $exception;
			shell_exec('echo `date` ' . $exception . ' >> mattype_errors.log');
			exit;
		}

	}
	
	/*
		Parses the ITYPE from the migrated item note field, and returns only the ITYPE number
	*/
	function get_itype_num($string)
	{
		$itypes = explode(':',$string);
		$itype_val = $itypes[1];
		$itype_val = trim($itype_val, ' ' );
		return $itype_val;
	}
	
	/*
		Turns the parsed simple XML into XML that can be returned in the PUT function
	*/
	function make_xml($xml)
	{
		$doc = new DOMDocument();
		$doc->formatOutput = TRUE;
		$doc->loadXML($xml->asXML());
		$return_xml = $doc->saveXML();
		return $return_xml;
	}
	
	/*
		Reads in campus Alma parameters
	*/
	$ini_array = parse_ini_file("update_mattype.ini");

	$key= $ini_array['apikey'];
	$baseurl = $ini_array['baseurl'];
	$campuscode = $ini_array['campuscode'];
	
	/*
		Read through every item record
		obtain internal note 3 field
		user mattypes csv file
		get item record xml so that i can send it back
		update (put xml) for item record with new material type
	*/

	  	$itype_mapping = fopen($argv[1],"r");
		$items_file = fopen($argv[2],"r");		
		$itype2mattype = array();
	
		/*
			Read in itype to alma mat type mapping
		*/
		while(($line = fgetcsv($itype_mapping)) !== FALSE)
		{
			$itype2mattype[$line[0]] = $line[1];
		}
		fclose($itype_mapping);
	
		var_dump($itype2mattype);
	
	
		/*
			Read in each line of the item csv file
			Call API for every item to get the XML for the item.  
			Get current itype value from mapped note field (may vary based on campus)
			Use mapping array to get the Alma material type for each ITYPE value
			PUT call to Alma API to replace the item material type
		*/
		$flag = true;
		while (($line = fgetcsv($items_file)) !== FALSE) 
		{
			// Ignores the first line of the file, the header
			if($flag) { $flag = false; continue; }
	
			/*
				Get info to call API
			*/
			$bib_id = $line[0];
			$holding_id = $line[1];
			$item_id = $line[2];
			
			// Use items CSV file to get the item information, and get XML for item from the Alma items API
			$link =  '/almaws/v1/bibs/'.$bib_id.'/holdings/'.$holding_id.'/items/'.$item_id;
			$url = $baseurl . $link . '?apikey=' . $key;
			$xml = getxml($url);

			/*
				Get new mat type, and replace current mat type with new mat type
			*/
			$current_mat_type = $xml->item_data->physical_material_type;
			// Will vary based on where campuses migrate the ITYPE to
			$itype_value = $xml->item_data->internal_note_3;
			$itype_value = get_itype_num($itype_value);
		
			// Not all itypes map directly to an item material type.  If there is no mapping, there is no update, and no PUT request		
			if(isset($itype2mattype[$itype_value]))
			{
				// Set new material type
				$new_mattype = $itype2mattype[$itype_value];
				echo $xml->item_data->pid . " " . $current_mat_type . " " . $itype_value . " " . $new_mattype . PHP_EOL;
				// Check if mapped material type is the same as current material type
				if(trim($new_mattype.'', ' ') != trim($current_mat_type.'', ' '))
				{
					// Set to new material type
					// Make XML ready for PUT request
					// Make PUT request
					$xml->item_data->physical_material_type = $new_mattype;
					$xml = make_xml($xml);
					$result = putxml($url,$xml);
					var_dump($result);

				}
				// If current material type is the same as mapped material type, no need for PUT request, no changes made
				else
				{
					echo  "Mat types are equal" .  PHP_EOL;
				}
			}
			else
			{
				echo "Item not updated";
				// Do nothing - there isn't an update required 
			}	
			

	
		}
			print $items_file . PHP_EOL;
			fclose($items_file);
		
?>
























