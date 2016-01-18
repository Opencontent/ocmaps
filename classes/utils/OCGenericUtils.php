<?php	

class OCGenericUtils
{
    private $DEFAULT_DATE_PATTERN = 'Y-m-d\TH:i:s';
    private $DEFAULT_STRING_SPLIT_REGEXP = '/\n|\r\n?/';
	
	//----------------------------------------------------------------------------------------------
	//
	//----------------------------------------------------------------------------------------------	
	public static function isEmptyString($stringToCheck){
		
		return (!isset($stringToCheck) || trim($stringToCheck)==='');
	}
	
	//----------------------------------------------------------------------------------------------
	//
	//----------------------------------------------------------------------------------------------	
	public static function convertDate($inputDate, $format=null){
		
		if($inputDate==null){
			return null;
		}
		
		if(self::isEmptyString($format)){
		
			//FIXME
			//$format = $this->DEFAULT_DATE_PATTERN;
			$format = 'Y-m-d\TH:i:s';

		}
			
		$dateTime = DateTime::createFromFormat($format, $inputDate);
		
		if ( $dateTime instanceOf DateTime ){

			$timestamp = $dateTime->format('U');

		}else{
		
			throw new Exception( 'Errore nella conversione della data: '.$inputDate );
		}
		
		return $timestamp;
	}
	
			
	//----------------------------------------------------------------------------------------------
	//
	//----------------------------------------------------------------------------------------------	
	public function loginAs($user){
	
		echo 'loginAs ' .$user. "\xA";
	
		$user = eZUser::fetchByName($user);
		eZUser::setCurrentlyLoggedInUser( $user , $user->attribute( 'contentobject_id' ) );	
	}
	
	//----------------------------------------------------------------------------------------------
	//
	//----------------------------------------------------------------------------------------------	
	public static function getRemoteFile( $url, $fileName, array $httpAuth = null, $debug = false, $allowProxyUse = true )
    {
        $url = trim( $url );
        $ini = eZINI::instance();
        $importINI = eZINI::instance( 'sqliimport.ini' );
        
        $localPath = $ini->variable( 'FileSettings', 'TemporaryDir' ).'/'.basename( $fileName );
        $timeout = $importINI->variable( 'ImportSettings', 'StreamTimeout' );

        $ch = curl_init( $url );
        $fp = fopen( $localPath, 'w+' );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_FILE, $fp );
        curl_setopt( $ch, CURLOPT_TIMEOUT, (int)$timeout );
        curl_setopt( $ch, CURLOPT_FAILONERROR, true );
		curl_setopt( $ch, CURLOPT_REFERER, 'http://pat-dev.opencontent.it/' );
		
        if ( $debug )
        {
            curl_setopt( $ch, CURLOPT_VERBOSE, true );
            curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
        }

        // Should we use proxy ?
        $proxy = $ini->variable( 'ProxySettings', 'ProxyServer' );
        if ( $proxy && $allowProxyUse )
        {
            curl_setopt( $ch, CURLOPT_PROXY, $proxy );
            $userName = $ini->variable( 'ProxySettings', 'User' );
            $password = $ini->variable( 'ProxySettings', 'Password' );
            if ( $userName )
            {
                curl_setopt( $ch, CURLOPT_PROXYUSERPWD, "$userName:$password" );
            }
        }
        
        // Should we use HTTP Authentication ?
        if( is_array( $httpAuth ) )
        {
            if( count( $httpAuth ) != 2 )
                throw new SQLIContentException( __METHOD__.' => HTTP Auth : Wrong parameter count in $httpAuth array' );
            
            list( $httpUser, $httpPassword ) = $httpAuth;
            curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
            curl_setopt( $ch, CURLOPT_USERPWD, $httpUser.':'.$httpPassword );
        }
        
        $result = curl_exec( $ch );
        if ( $result === false )
        {
            $error = curl_error( $ch );
            $errorNum = curl_errno( $ch );
            curl_close( $ch );
            throw new SQLIContentException( "Failed downloading remote file '$url'. $error", $errorNum);
        }
        
        curl_close( $ch );
        fclose( $fp );

            
        return trim($localPath);
    }

    //----------------------------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------------------------
    public static function print_r_to_string($anyObject){
        $myString = print_r($anyObject, TRUE);
        return $myString;
    }

    //----------------------------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------------------------
    public static function getObjectAttrNameAsArray($anyObject){

        $finalArray = array();
        $properties = get_object_vars($anyObject);

        foreach($properties as $key => $value) {
            $finalArray[$key] =$key;
        }

        return $finalArray;
    }

    //----------------------------------------------------------------------------------------------
    //converte stringa in array data un espressione regolare
    //----------------------------------------------------------------------------------------------
    public static function getArrayFromString($anyString, $regExp=null){

        //se la stringa è vuota torna null
        if(self::isEmptyString($anyString)){
            return null;
        }

        //se non viene passata espressione regolare usa quella di default, ovvero l'accapo di ogni riga
        if(self::isEmptyString($regExp)){
            //$regExp = $this->DEFAULT_STRING_SPLIT_REGEXP
            $regExp = '/\n|\r\n?/';
        }

        //esegue lo split
        return preg_split($regExp, $anyString);
    }


}
	
?>