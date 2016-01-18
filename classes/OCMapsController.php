<?php	

class OCMapsController
{
    private $settingsINI;

    private $mappaObjNode;

    private $hiddenMarkerStatusCode;
    private $mappa;
    private $geoChildren;

    public  $geoChildrenFull;


    //valorizzati sia in modalita mappa locale che remota
    public $geoDataForFitBounds = '';
    public $geoDataAsArray;
    public $geoDataStatusArray;

    //------------------------------------------------------------------------------------------------
    //definisco le funzioni esistenti in questa classe
    //------------------------------------------------------------------------------------------------

    function __construct()
    {
        $this->geoDataForFitBounds  = '';
        $this->Operators =
            array(
                'init',
                'getGeoDataAsArray',
                'getGeoDataAsGeoJSON',
                'getGeoDataForFitBounds',
                'getGeoDataStatusArray',
                'showFilterSelect',
                'showLinkInPopup',
                'showFilterSelect',
                'getJsonArea'
                );

        $this->settingsINI = eZINI::instance( 'settings.ini' );

        $this->hiddenMarkerStatusCode = $this->settingsINI->variable('GlobalSettings','hiddenMarkerStatusCode');
    }

    function operatorList()
    {
        return $this->Operators;
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    //------------------------------------------------------------------------------------------------
    //definisco i parametri per le funzioni
    //------------------------------------------------------------------------------------------------
    function namedParameterList()
    {
        return array(

            'init' => array(
                'nodeId' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => ''
                )
            ),
            'getGeoDataAsArray' => array(
                'status' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'getGeoDataAsGeoJSON' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'getGeoDataForFitBounds' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'getGeoDataStatusArray' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'showFilterSelect' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'showLinkInPopup' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'showFilterSelect' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            ),
            'getJsonArea' => array(
                'inputValue' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                )
            )
        );
    }

    //------------------------------------------------------------------------------------------------
    //redirigo sulle funzioni chiamate da template
    //------------------------------------------------------------------------------------------------
    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace,$currentNamespace, &$operatorValue, $namedParameters )
    {
        if ($operatorName == 'init')
        {
            $operatorValue = $this->init($namedParameters['nodeId']);
        }
        if ($operatorName == 'getGeoDataAsArray')
        {
            $operatorValue = $this->getGeoDataAsArray($namedParameters['status']);
        }
        if ($operatorName == 'getGeoDataAsGeoJSON')
        {
            $operatorValue = $this->getGeoDataAsGeoJSON($namedParameters['inputValue']);
        }
        if ($operatorName == 'getGeoDataForFitBounds')
        {
            $operatorValue = $this->getGeoDataForFitBounds($namedParameters['inputValue']);
        }
        if ($operatorName == 'getGeoDataStatusArray')
        {
            $operatorValue = $this->getGeoDataStatusArray($namedParameters['inputValue']);
        }
        if ($operatorName == 'showFilterSelect')
        {
            $operatorValue = $this->showFilterSelect($namedParameters['inputValue']);
        }
        if ($operatorName == 'showLinkInPopup')
        {
            $operatorValue = $this->showLinkInPopup($namedParameters['inputValue']);
        }
        if ($operatorName == 'showFilterSelect')
        {
            $operatorValue = $this->showFilterSelect($namedParameters['inputValue']);
        }
        if ($operatorName == 'getJsonArea')
        {
            $operatorValue = $this->getJsonArea($namedParameters['inputValue']);
        }
    }

    //------------------------------------------------------------------------------------------------
    //caricare qui tutti i dati necessari una sola volta
    //e metterli in attributi di classe
    //------------------------------------------------------------------------------------------------
    public function init($nodeId){

        $this->loadMappa($nodeId);

        $dataMap = $this->mappa->attribute('data_map');


        //se non è impostato un datasource esterno prende i dati dai figli geolocalizzati
        if(OCGenericUtils::isEmptyString($dataMap['data_source']->DataText)){

            $this->loadGeoChildrenByNodeId($nodeId);

        //se è stato impostato un data source, questo deve essere un json fatto in un certo modo
        //e questo metodo lo straduce in una forma comoda per la mappa
        }else{

            $this->loadGeoChildrenByDataSource($dataMap['data_source']->DataText);
        }

    }

    //------------------------------------------------------------------------------------------------
    //carica i figli dato il nodo del padre
    //------------------------------------------------------------------------------------------------
    private function loadGeoChildrenByNodeId($nodeId){

        $params = array();
        $params['Depth'] = 1;

        //estrae il tipo delle classi figle da considerare
        //attributo class_name (Classe dei figli georeferenziati)
        $classFilterType = $this->getGeoChildClassName();

        if(!OCGenericUtils::isEmptyString($classFilterType)){
            $params['ClassFilterType'] = $classFilterType;
        }

        //imposto lo stesso ordinamento scelto da backend
        $params['SortBy'] = $this->mappaObjNode->attribute( 'sort_array' );

        $this->geoChildren = eZContentObjectTreeNode::subTreeByNodeId($params, $nodeId);
        $this->geoChildrenFull = $this->geoChildren;

        $this->createGeoDataAsArray();
    }

    //------------------------------------------------------------------------------------------------
    // carica i dati necessari alla mappa da url in cui è esposto un json
    //------------------------------------------------------------------------------------------------
    private function loadGeoChildrenByDataSource($data_source){

        $unparsed_json = file_get_contents($data_source);

        $dataAssociativeArray = json_decode($unparsed_json, true);


        $this->geoDataAsArray = $dataAssociativeArray['geoDataAsArray'];
        $this->geoDataForFitBounds = $dataAssociativeArray['geoDataForFitBounds'];
        $this->geoDataStatusArray = $dataAssociativeArray['geoDataStatusArray'];

    }

    //------------------------------------------------------------------------------------------------
    //recupera il nome della classe dei sotto-elementi
    //------------------------------------------------------------------------------------------------
    private function getGeoChildClassName(){

        $dataMap = $this->mappa->attribute('data_map');
        return $dataMap['allowed_child_class_name'];
    }

    //------------------------------------------------------------------------------------------------
    //carica il nome dell'attributo GMap dei sotto-elementi
    //------------------------------------------------------------------------------------------------
    private function getGeoChildGeoAttributeName(){

        $dataMap = $this->mappa->attribute('data_map');

        $eZCOA = $dataMap['child_geo_attribute_name'];

        return $eZCOA->DataText;

    }

    //------------------------------------------------------------------------------------------------
    //carica l'attributo da mostrare nel popup degli sotto-elementi
    //------------------------------------------------------------------------------------------------
    private function getGeoChildPupUpAttributeName(){

        $dataMap = $this->mappa->attribute('data_map');

        $eZCOA = $dataMap['child_popup_attribute_name'];
		
        if(!isset($eZCOA)){
            $eZCOA = $dataMap['name'];
        }

        return $eZCOA->DataText;

    }
    //------------------------------------------------------------------------------------------------
    //carica l'attributo dove viene indicato lo stato dei sotto-elementi
    //------------------------------------------------------------------------------------------------
    private function getGeoChildStatusAttributeName(){

        $dataMap = $this->mappa->attribute('data_map');

        $eZCOA = $dataMap['child_status_attribute_name'];

        if(!isset($eZCOA)){
            $eZCOA = $dataMap['status'];
        }

        return $eZCOA->DataText;

    }

    //------------------------------------------------------------------------------------------------
    //carica l'oggetto mappa dal nodo
    //------------------------------------------------------------------------------------------------
    private function loadMappa($nodeId){

        $this->mappaObjNode = eZContentObjectTreeNode::fetch($nodeId);
        $this->mappa = $this->mappaObjNode->ContentObject;


    }

    //------------------------------------------------------------------------------------------------
    //carica un oggetto GMap a partire dall'attributo geo dell'oggetto figlio della classe Mappa
    //------------------------------------------------------------------------------------------------
    private function loadGeoObjOfChild($child){

        $obj = $child->ContentObject;

        $dataMap = $obj->attribute('data_map');

        $geoAttr = $dataMap[$this->getGeoChildGeoAttributeName()];

        return eZGmapLocation::fetch($geoAttr->ID, $geoAttr->Version);
    }

    //------------------------------------------------------------------------------------------------
    //estrae il testo della popup dal titolo della classe
    //------------------------------------------------------------------------------------------------
    private function getPopupText($child){

        $obj = $child->ContentObject;

        $dataMap = $obj->attribute('data_map');
        $geoAttr = $dataMap[$this->getGeoChildPupUpAttributeName()];
		
        if(isset($geoAttr)){
			return preg_replace('/[^A-Za-z0-9]/', ' ', $geoAttr->attribute('data_text'));
		}
    }

    //------------------------------------------------------------------------------------------------
    //estrae l'eventuale icona associata allo stato
    //------------------------------------------------------------------------------------------------
    private function getStatus($child){

        $ezContentObject = $this->getStatusEzContentObject($child);

        $statusAssociativeArray = array();

        if($ezContentObject){

            $dataMap = $ezContentObject->attribute('data_map');
            $iconAttribute = $dataMap['marker_icon'];

            if(isset($iconAttribute)){

                //ritorno il nome dell'icona da mostrare
                $statusAssociativeArray['marker_icon'] = $iconAttribute->attribute('data_text');
            }

            $codeAttribute = $dataMap['code'];

            if(isset($codeAttribute)){

                //ritorno il nome dell'icona da mostrare
                $statusAssociativeArray['code'] = $codeAttribute->attribute('data_text');
            }

        }

        return $statusAssociativeArray;
    }

    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    private function getStatusEzContentObject($child){

        $obj = $child->ContentObject;

        $dataMap = $obj->attribute('data_map');
        $statusAttr = $dataMap[$this->getGeoChildStatusAttributeName()];

        if(isset($statusAttr)){

            return $this->getFirstEzContentObjectOfRelation($statusAttr);
        }
    }


    //------------------------------------------------------------------------------------------------
    //prende il primo elemento di una realazione 1 a N
    //------------------------------------------------------------------------------------------------
    private function getFirstEzContentObjectOfRelation($ezContentObjectAttribute){

        $content = $ezContentObjectAttribute->content();
        $relationList = $content['relation_list'];

        return eZContentObject::fetch($relationList[0]['contentobject_id'], $relationList[0]['contentobject_version']);

    }


    //------------------------------------------------------------------------------------------------
    //da invocare sempre dopo l'init
    //ritorna un array associativo di valori da mostrare nel template
    //------------------------------------------------------------------------------------------------
    function createGeoDataAsArray()
    {

        $geoData = array();
        $filteredGeoChildren = array();

            foreach ($this->geoChildren as $child) {

                $geoObj = $this->loadGeoObjOfChild($child);

                if($geoObj!=null){

                    $latitude = $geoObj->attribute('latitude');
                    $longitude = $geoObj->attribute('longitude');

                    //se anche solo uno dei due attributi della geolocalizzazione non è varlozziato
                    //non considero l'oggetto
                    if(OCGenericUtils::isEmptyString($latitude) || OCGenericUtils::isEmptyString($longitude)){
                        continue;
                    }

                    $dataMap = $child->attribute('data_map');

                    $geoDataSubArray = array();
                    $geoDataSubArray['lat'] = $latitude ;
                    $geoDataSubArray['lon'] = $longitude;
                    $geoDataSubArray['name'] = $child->Name;
                    $geoDataSubArray['popupMsg'] = $this->getPopupText($child);

                    $statusAssociativeArray = $this->getStatus($child);

                    $geoDataSubArray['status_code'] = $statusAssociativeArray['code'];
                    $geoDataSubArray['marker_icon'] = $statusAssociativeArray['marker_icon'];

                    $href = $child->urlAlias();
                    eZURI::transformURI( $href, false, 'full' );

                    $geoDataSubArray['urlAlias'] = $href;

                    array_push($geoData, $geoDataSubArray);
                    array_push($filteredGeoChildren, $child);

                }

            }

        //mostro solo i figli validi
        $this->geoChildren = $filteredGeoChildren;

        //creo il fitbound per fare il crop della mappa attrono ai punti esistenti
        $this->createGeoDataForFitBounds($geoData);

        //creo l'array di status
        $this->createGeoDataStatusArray();

        $this->geoDataAsArray = $geoData;

    }


    public function getGeoDataAsArray($status){

        $filteredArray = array();

        foreach ($this->geoDataAsArray as $child) {

            if(!$this->hasAllowedStatus($child, $status)){
                continue;
            }

            array_push($filteredArray, $child);
        }

        return $filteredArray;
    }

    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    private function hasAllowedStatus($child, $status){

            $codeAttribute = $child['status_code'];

            if(!OCGenericUtils::isEmptyString($codeAttribute)){

                if((!OCGenericUtils::isEmptyString($status) && $codeAttribute == $status) || (OCGenericUtils::isEmptyString($status) && $codeAttribute!=$this->hiddenMarkerStatusCode)){
                    return true;
                }else{
                    return false;
                }

            }else{
                return true;
            }
    }


    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    public function createGeoDataStatusArray(){

        foreach ($this->geoChildrenFull as $child) {

            $ezContentObject = $this->getStatusEzContentObject($child);

            if($ezContentObject){

                $dataMap = $ezContentObject->attribute('data_map');
                $iconAttribute = $dataMap['marker_icon'];

                if(isset($iconAttribute)){

                    $count = 1;
                    $code = $dataMap['code'];

                    if($code->attribute('data_text')==$this->hiddenMarkerStatusCode){
                        continue;
                    }

                    $description = $dataMap['description'];

                    if (array_key_exists($code->attribute('data_text'), $this->geoDataStatusArray)) {

                        //gestisco il contatore
                        $innerArray = $this->geoDataStatusArray[$code->attribute('data_text')];
                        $count = $innerArray['count'] + 1;
                    }

                    //creo l'array di status (se esistono)
                    //questo array è usato a video per mostarre la legenda degli status
                    //l'array, quindi la legenda dei markers, viene creata solo per quei marker impostati nell'oggetto status
                    //altrimenti il sistema usa l'icona di default, ma questa non ha senso mostrarla in una legenda
                    $this->geoDataStatusArray[$code->attribute('data_text')] = array('description' => $description->attribute('data_text'), 'marker_icon'=>$iconAttribute->attribute('data_text'), 'code'=>$code->attribute('data_text'), 'count'=>$count);

                }

            }
        }

        return $this->geoDataStatusArray;
    }

    public function getGeoDataStatusArray(){

        return $this->geoDataStatusArray;
    }

    //------------------------------------------------------------------------------------------------
    //ritorna una stringa correttamente formattata per eseguire il map.fitBounds
    //in modo che la mappa venga centrata in un intorno dei marker posizionati
    //------------------------------------------------------------------------------------------------
    function getGeoDataForFitBounds( $args )
    {
        return $this->geoDataForFitBounds;
    }

    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    function showLinkInPopup($inputValue)
    {
        $dataMap = $this->mappa->attribute('data_map');

        $eZCOA = $dataMap['show_link_in_popup'];

        return $eZCOA->DataInt;
    }

    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    function showFilterSelect($inputValue)
    {
        $dataMap = $this->mappa->attribute('data_map');

        $eZCOA = $dataMap['show_filter_select'];

        return $eZCOA->DataInt;
    }

    //------------------------------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------------------------------
    function getJsonArea($inputValue)
    {

        $dataMap = $this->mappa->attribute('data_map');

        $eZJF = $dataMap['json_area_file_name'];

        if(!OCGenericUtils::isEmptyString($eZJF->DataText)){

            return file_get_contents(getcwd().'/extension/ocmaps/design/standard/javascript/json/'.$eZJF->DataText);

        }else{
            return null;
        }
    }

    //------------------------------------------------------------------------------------------------
    //crea una stringa correttamente formattata per eseguire il map.fitBounds
    //in modo che la mappa venga centrata in un intorno dei marker posizionati
    //------------------------------------------------------------------------------------------------
    private function createGeoDataForFitBounds($geoData){

        $this->geoDataForFitBounds = '[';

        foreach ($geoData as $value) {
            $this->geoDataForFitBounds = $this->geoDataForFitBounds.'['.$value['lat'].','.$value['lon'].'],';
        }

        $this->geoDataForFitBounds = rtrim($this->geoDataForFitBounds, ",");
        $this->geoDataForFitBounds = $this->geoDataForFitBounds.']';


    }

    private $Operators;
}

	
?>