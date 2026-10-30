<?php

if(isset($this->collArr) && $this->collArr){
	foreach($this->collArr as $collid => $collData){
		$collectionName = '';
		if(is_array($collData) && array_key_exists('collname', $collData)){
			$collectionName = $collData['collname'];
            $collectionName = str_replace('NEON Biorepository ', '', $collectionName);
		}
		echo "\nNEON (National Ecological Observatory Network) Biorepository. ";
		echo $collectionName . ". ";
		echo "Data accessed from ";
        echo GeneralUtil::getDomain() . $GLOBALS['CLIENT_ROOT'];
		echo "/collections/misc/neoncollprofiles.php?collid=";
		echo $collid;
		echo " on ";
		echo date('Y-m-d');
		echo ". ";
		echo "Licensed under CC BY 4.0 ";
		echo "(https://creativecommons.org/licenses/by/4.0/). ";
		echo "Data archived at [your DOI]. \n";
	}
}
?>